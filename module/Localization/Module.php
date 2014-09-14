<?php
namespace Localization;

use Localization\Service\Localization as LocalizationService;
use Acl\Model\AclBase as AclBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Console\Request as ConsoleRequest;
use Zend\Validator\AbstractValidator;
use Zend\Mvc\MvcEvent;
use Zend\Http\Header\SetCookie;
use User\Service\UserIdentity as UserIdentityService;
use Exception;
use Locale;

class Module
{
    /**
     * User identity
     * @var array
     */
    protected $userIdentity;

    /**
     * List of registered localizations
     * @var array
     */
    protected $localizations;

    /**
     * Default localization
     * @var array
     */
    protected $defaultLocalization;

    /**
     * Service managerzend
     * @var object
     */
    protected $serviceManager;

    /**
     * Module manager
     * @var object
     */
    protected $moduleManager;

    /**
     * Localization cookie
     */ 
    CONST LOCALIZATION_COOKIE = 'language';

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get the service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');

        // get the module manager
        $this->moduleManager = $moduleManager;

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
    }

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $e)
    {
        $request = $this->serviceManager->get('Request');

        if (!$request instanceof ConsoleRequest) {
            // init user localization
            $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, [
                $this, 'initUserLocalization'
            ], 100);
        }
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(ModuleEvent $e)
    {
        $this->userIdentity = UserIdentityService::getCurrentUserIdentity();

        // init default localization
        $this->initDefaultLocalization();
    }

    /**
     * Init default localization
     */
    private function initDefaultLocalization()
    {
        try {
            // get all registered localizations
            $localization = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Localization\Model\LocalizationBase');

            // init default localization
            $this->localizations = $localization->getAllLocalizations();
            $acceptLanguage = Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'));

            $defaultLanguage = !empty($this->userIdentity['language'])
                ? $this->userIdentity['language']
                : ($acceptLanguage ? substr($acceptLanguage, 0, 2) : null);

            // setup locale
            $this->defaultLocalization =  array_key_exists($defaultLanguage, $this->localizations)
                ? $this->localizations[$defaultLanguage]
                : current($this->localizations);

            // init translator settings
            $translator = $this->serviceManager->get('translator');
            $translator->setLocale($this->defaultLocalization['locale']);

            // add a cache for translator
            $request = $this->serviceManager->get('Request');

            if (!$request instanceof ConsoleRequest) {
                $translator->setCache($this->serviceManager->get('Application\Cache\Dynamic'));
            }

            // init default localization
            Locale::setDefault($this->defaultLocalization['locale']);

            AbstractValidator::setDefaultTranslator($translator);
            LocalizationService::setCurrentLocalization($this->defaultLocalization);
            LocalizationService::setLocalizations($this->localizations);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Init user localization
     *
     * @param object $e MvcEvent
     */
    public function initUserLocalization(MvcEvent $e)
    {
        try {
            // get a router
            $router = $this->serviceManager->get('router');
            $matches = $e->getRouteMatch();

            if (!$matches->getParam('language') 
                    || !array_key_exists($matches->getParam('language'), $this->localizations)) {

                if (!$matches->getParam('language')) {
                    // set default language
                    $router->setDefaultParam('language', $this->defaultLocalization['language']);

                    // remember user's choosen language
                    $this->setUserLanguage($this->defaultLocalization['language']);
                    return;
                }

                // show a 404 page
                return $matches->setParam('action', 'not-found');
            }

            // init an user localization
            if ($this->defaultLocalization['language'] != $matches->getParam('language')) {
                $this->serviceManager
                    ->get('translator')
                    ->setLocale($this->localizations[$matches->getParam('language')]['locale']);

                LocalizationService::setCurrentLocalization($this->localizations[$matches->getParam('language')]);    
            }

            Locale::setDefault($this->localizations[$matches->getParam('language')]['locale']);
            $router->setDefaultParam('language', $matches->getParam('language'));

            // remember user's choose language
            $this->setUserLanguage($matches->getParam('language'));
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Set user's language
     *
     * @param string $language
     * @return void
     */
    protected function setUserLanguage($language)
    {
        if (!$this->userIdentity['language'] || $this->userIdentity['language'] != $language) {
            // save language
            if ($this->userIdentity['role'] != AclBaseModel::DEFAULT_ROLE_GUEST) {
                $model = $this->serviceManager
                    ->get('Application\Model\ModelManager')
                    ->getInstance('User\Model\UserBase')
                    ->setUserLanguage($this->userIdentity['user_id'], $language);
            }

            // set language cookie
            $header = new SetCookie();
            $header->setName(self::LOCALIZATION_COOKIE)
                ->setValue($language)
                ->setPath('/')
                ->setExpires(time() + (int) SettingService::getSetting('application_localization_cookie_time'));

            $this->serviceManager->get('Response')->getHeaders()->addHeader($header);
            $this->userIdentity['language'] = $language;

            // change globally user's identity
            UserIdentityService::setCurrentUserIdentity($this->userIdentity);

            $authService = $this->serviceManager->get('User\AuthService');
            $authService->getStorage()->write($this->userIdentity);
        }
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php',
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }


    /**
     * Get service config
     */
    public function getServiceConfig()
    {
        return [
        ];
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'localization' => function() {
                    return new \Localization\View\Helper\
                            Localization(LocalizationService::getCurrentLocalization(), LocalizationService::getLocalizations());
                }
            ]
        ];
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}