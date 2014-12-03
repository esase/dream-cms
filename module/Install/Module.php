<?php
namespace Install;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Validator\AbstractValidator;
use Zend\Mvc\MvcEvent;
use Locale;

class Module
{
    /**
     * Service managerzend
     * @var object
     */
    protected $serviceLocator;

    /**
     * List of registered localizations
     * @var array
     */
    protected static $localizations;

    /**
     * Default localization
     * @var array
     */
    protected static $defaultLocalization;

    /**
     * Current localization
     * @var array
     */
    protected static $currentLocalization;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
    }

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $e)
    {
        $request = $this->serviceLocator->get('Request');

        // init user localization
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, [
            $this, 'initUserLocalization'
        ], 100);
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(ModuleEvent $e)
    {
        // init php settings
        $this->initPhpSettings();

        // init default localization
        $this->initDefaultLocalization();
    }

    /**
     * Init default localization
     */
    protected function initDefaultLocalization()
    {
        self::$localizations = $this->serviceLocator->get('config')['install_languages'];
        $this->serviceLocator->get('translator');

        $acceptLanguage = Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'));
        
        $defaultLanguage = $acceptLanguage 
            ? substr($acceptLanguage, 0, 2) 
            : null;

        // setup locale
        self::$defaultLocalization =  array_key_exists($defaultLanguage, self::$localizations)
            ? self::$localizations[$defaultLanguage]
            : current(self::$localizations);

        // init translator settings
        $translator = $this->serviceLocator->get('translator');
        $translator->setLocale(self::$defaultLocalization['locale']);

        // init default localization
        Locale::setDefault(self::$defaultLocalization['locale']);
        AbstractValidator::setDefaultTranslator($translator);
        self::$currentLocalization = self::$defaultLocalization;
    }

    /**
     * Init user localization
     *
     * @param object $e MvcEvent
     */
    public function initUserLocalization(MvcEvent $e)
    {
        // get a router
        $router = $this->serviceLocator->get('router');
        $matches = $e->getRouteMatch();

        if (!$matches->getParam('language') 
                || !array_key_exists($matches->getParam('language'), self::$localizations)) {

            if (!$matches->getParam('language')) {
                // set default language
                $router->setDefaultParam('language', self::$defaultLocalization['language']);
                return;
            }

            // show a 404 page
            return $matches->setParam('action', 'not-found');
        }

        // init an user localization
        if (self::$defaultLocalization['language'] != $matches->getParam('language')) {
            $this->serviceLocator
                ->get('translator')
                ->setLocale(self::$localizations[$matches->getParam('language')]['locale']);

            self::$currentLocalization = self::$localizations[$matches->getParam('language')];
        }

        Locale::setDefault(self::$localizations[$matches->getParam('language')]['locale']);
        $router->setDefaultParam('language', $matches->getParam('language'));
    }

    /**
     * Init php settings
     */
    protected function initPhpSettings()
    {
        $config = $this->serviceLocator->get('Config');

        if (!empty($config['php_settings'])) {
            foreach($config['php_settings'] as $settingName => $settingValue) {
                ini_set($settingName, $settingValue);
            }
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
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Install\Model\InstallBase' => function() {
                    return new Model\InstallBase();
                }
            ]
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
                    return new \Install\View\Helper\Localization(self::$currentLocalization, self::$localizations);
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