<?php

namespace Application;

use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Application\Model\Acl as Acl;
use StdClass;
use DateTime;

use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

use Zend\Session\SessionManager;
use Zend\Session\Container as SessionContainer;

use Zend\Validator\AbstractValidator;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Module manager
     * @var object
     */
    protected $moduleManager;

    /**
     * User identity
     * @var object
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
     * Init
     *
     * @param object $moduleManager
     */
    function init(\Zend\ModuleManager\ModuleManager $moduleManager)
    {
        // get service manager
        $this->serviceManager =
        $moduleManager->getEvent()->getParam('ServiceManager');

        // get module manager
        $this->moduleManager = $moduleManager;

        $moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULES_POST,
            array($this, 'initApplication'));
    }

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $e)
    {
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'initUserLocalization'
        ), 100);
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(\Zend\ModuleManager\ModuleEvent $e)
    {
        // init session
        $this->initSession();

        // init user identity
        $this->initUserIdentity();

        // init time zone
        $this->initTimeZone();

        // init php settings
        $this->initPhpSettings();

        // init default localization
        $this->initDefaultLocalization();
    }

    /**
     * Init session
     */
    protected function initSession()
    {
        $session = $this->serviceManager->get('Zend\Session\SessionManager');
        $session->start();

        $container = new SessionContainer('initialized');

        if (!isset($container->init)) {
            $session->regenerateId(true); $container->init = 1;
        }
    }

    /**
     * Init identity
     */
    protected function initUserIdentity()
    {
        $authService = $this->serviceManager->get('Application\AuthService');

        if (!$authService->hasIdentity()) {
            $this->userIdentity = new stdClass();
            $this->userIdentity->role = Acl::DEFAULT_ROLE_GUEST;
            $this->userIdentity->user_id = Acl::DEFAULT_GUEST_ID;

            $authService->getStorage()->write($this->userIdentity);
        }
        else {
            $this->userIdentity = $authService->getIdentity();
        }
    }

    /**
     * Init time zone
     */
    protected function initTimeZone()
    {
        $config = $this->serviceManager->get('Config');

        $defaultTimeZone = !empty($this->userIdentity->time_zone)
            ? $this->userIdentity->time_zone
            : $config['default_timezone'];

        // change time zone settings
        if ($defaultTimeZone != date_default_timezone_get()) {
            date_default_timezone_set($defaultTimeZone);
        }

 	// get difference to greenwich time (GMT) with colon between hours and minutes
        $date = new DateTime();

        $applicationInit = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Init');

        // change time zone settings in model
        $applicationInit->initTimeZone($date->format('P'));
    }

    /**
     * Init php settings
     */
    protected function initPhpSettings()
    {
        $config = $this->serviceManager->get('Config');

        if (!empty($config['php_settings'])) {
            foreach($config['php_settings'] as $settingName => $settingValue) {
                ini_set($settingName, $settingValue);
            }
        }
    }

    /**
     * Init default localization
     */
    private function initDefaultLocalization()
    {
        // get all registered localizations
        $localization = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Localization');

        // init default localization
        $this->localizations = $localization->getAllLocalizations();
        $acceptLanguage = \Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'));

        $defaultLanguage = !empty($this->userIdentity->language)
            ? $this->userIdentity->language
            : ($acceptLanguage ? substr($acceptLanguage, 0, 2) : null);

        // setup locale
        $this->defaultLocalization =  array_key_exists($defaultLanguage, $this->localizations)
            ? $this->localizations[$defaultLanguage]
            : current($this->localizations);

        // init translator settings
        $translator = $this->serviceManager->get('translator');
        $translator->setLocale($this->defaultLocalization['locale']);
        $translator->setCache($this->serviceManager->get('Cache\Dynamic'));

        AbstractValidator::setDefaultTranslator($translator);
    }

    /**
     * Init user localization
     *
     * @param object $e MvcEvent
     */
    public function initUserLocalization(MvcEvent $e)
    {
        $router = $this->serviceManager->get('router');
        $matches = $e->getRouteMatch();

        // get languge param from the route
        if (!array_key_exists($matches->getParam('languge'), $this->localizations)) {
            $router->setDefaultParam('languge', $this->defaultLocalization['language']);
            return;
        }

        // init user localization
        if ($this->defaultLocalization['language'] != $matches->getParam('languge')) {
            $this->serviceManager
                ->get('translator')
                ->setLocale($this->localizations[$matches->getParam('languge')]['locale']);
        }

        $router->setDefaultParam('languge', $matches->getParam('languge'));
    }

    /**
     * Get config
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service config
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Application\Acl' => function($serviceManager)
                {
                    return new Acl();
                },
                'Application\Model\Builder' => function($serviceManager)
                {
                    return new Model\ModelBuilder($serviceManager);
                },
                'Application\AuthService' => function($serviceManager)
                {
                    $authAdapter = new DbTableAuthAdapter($serviceManager->get('Zend\Db\Adapter\Adapter'),
                            'users', 'nick_name', 'password', 'SHA1(CONCAT(MD5(?), salt))');

                    $authService = new AuthenticationService();
                    $authService->setAdapter($authAdapter);

                    return $authService;
                },
                'Cache\Static' => function ($serviceManager)
                {
                    $config = $serviceManager->get('Config');
                    $cache =\Zend\Cache\StorageFactory::factory(array(
                        'adapter' => array(
                            'name' => $config['static_cache']['type']
                        ),
                        'plugins' => array(
                            // Don't throw exceptions on cache errors
                            'exception_handler' => array(
                                'throw_exceptions' => false
                            ),
                            'Serializer'
                        )
                    ));

                    $cache->setOptions($config['static_cache']['options']);
                    return $cache;
                },
                'Cache\Dynamic' => function ($serviceManager)
                {
                    $config = $serviceManager->get('Config');
                    $cache = \Zend\Cache\StorageFactory::factory(array(
                        'adapter' => array(
                            'name' => $config['dynamic_cache']['type']
                        ),
                        'plugins' => array(
                            // Don't throw exceptions on cache errors
                            'exception_handler' => array(
                                'throw_exceptions' => false
                            ),
                            'Serializer'
                        )
                    ));

                    $cache->setOptions($config['dynamic_cache']['options']);
                    return $cache;
                },
                'Custom\Cache\Static\Utils' => function ($serviceManager)
                {
                   return new \Custom\Cache\Utils($serviceManager->get('Cache\Static'));
                },
                'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
                'Zend\Session\SessionManager' => function ($serviceManager)
                {
                    $config = $serviceManager->get('config');

                    // get session config
                    $sessionConfig = new
                    $config['session']['config']['class']();
                    $sessionConfig->setOptions($config['session']['config']['options']);

                    // get session storage
                    $sessionStorage = new $config['session']['storage']();

                    $sessionSaveHandler = null;
                    if (!empty($config['session']['save_handler'])) {
                        // class should be fetched from service manager since it
                        // will require constructor arguments
                        $sessionSaveHandler = $serviceManager->get($config['session']['save_handler']);
                    }

                    // get session manager
                    $sessionManager = new SessionManager($sessionConfig,
                    $sessionStorage, $sessionSaveHandler);
                    
                    if (!empty($config['session']['validators'])) {
                        $chain = $sessionManager->getValidatorChain();

                        foreach ($config['session']['validators'] as $validator) {
                            $chain->attach('session.validate', array(new $validator(), 'isValid'));
                        }
                    }

                    SessionContainer::setDefaultManager($sessionManager);
                    return $sessionManager;
                }
            )
        );
    }

    /** 
     * Get view helper config
     */
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'flashMessages' => function($sm) {
                    $flashmessenger = $sm->getServiceLocator()
                        ->get('ControllerPluginManager')
                        ->get('flashmessenger');
 
                    $messages = new \Application\View\Helper\FlashMessages();
                    $messages->setFlashMessenger($flashmessenger);
 
                    return $messages;
                }
            )
        );
    }

    /**
     * Get autoloader config
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php'
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                )
            )
        );
    }
}
