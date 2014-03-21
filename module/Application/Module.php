<?php

namespace Application;

use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Http\Response;

use Application\Model\Acl as AclModelBase;
use User\Model\Base as UserBaseModel;
use Application\Service\Service as ApplicationService;
use Application\View\Resolver\TemplatePathStack;
use User\Service\Service as UserService;
use Zend\Http\Header\SetCookie;

use StdClass;
use DateTime;
use Locale;
use Exception;

use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

use Zend\Validator\AbstractValidator;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Log\Writer\FirePhp as FirePhp;
use Zend\Log\Logger as Logger;

use Zend\Cache\StorageFactory as CacheStorageFactory;
use Zend\ModuleManager\ModuleManager;

use Zend\Session\Container as SessionContainer;
use Zend\Session\SessionManager;
use Zend\Console\Request as ConsoleRequest;
use Application\Utility\ErrorLogger;

class Module
{
    /**
     * Init admin layout
     * @param boolean
     */
    protected $initAdminlayout = false;

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
     * Administration area
     */
    const ADMINISTRATION_AREA = 'administration';

    /**
     * Localization cookie
     */ 
    CONST LOCALIZATION_COOKIE = 'language';

    /**
     * Init
     *
     * @param object $moduleManager
     */
    function init(ModuleManager $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');

        // get module manager
        $this->moduleManager = $moduleManager;

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, array($this, 'initApplication'));
    }

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $e)
    {
        // log errors
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($e){
            if (null != ($exception = $e->getParam('exception'))) {
                ErrorLogger::log($exception);
            }
        });

        // init user localization
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'initUserLocalization'
        ), 100);

        // check administration privileges
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'checkAdministrationPrivileges'
        ), 2);

        // load admin layout
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'loadAdministrationLayout'
        ));

        $config = $this->serviceManager->get('Config');

        // init profiler
        if ($config['profiler']) {
            $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_FINISH, array(
                $this, 'initProfiler'
            ));
        }
    }

    /**
     * Load administration layout
     *
     * @param object $e MvcEvent
     */
    public function loadAdministrationLayout(MvcEvent $e)
    {
        try {
            if ($this->initAdminlayout) {
                $e->getTarget()->layout('layout/administration');
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Check administration privileges
     *
     * @param object $e MvcEvent
     */
    public function checkAdministrationPrivileges(MvcEvent $e)
    {
        try {
            $matches = $e->getRouteMatch();
            $controller = $matches->getParam('controller');
            $action = $matches->getParam('action');
    
            // check the controller's name
            if (false !== ($result = stristr($controller, self::ADMINISTRATION_AREA))) {
                if ($e->getResponse()->getStatusCode() != Response::STATUS_CODE_404) {
                    // check the action's permission
                    if (!UserService::checkPermission($controller . ' ' . $action, false)) {
                        // redirect to the forbidden page
                        $response = $e->getResponse();
                        $router = $e->getRouter();
                        $url = $router->assemble(array('controller' =>
                            'error', 'action' => 'forbidden'), array('name' => 'application'));
    
                        // populate and return the response
                        $response->setStatusCode(Response::STATUS_CODE_302);
                        $response->getHeaders()->addHeaderLine('Location', $url);
    
                        return $response;
                    }
    
                    $this->initAdminlayout = true;
                }
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init profiler
     */
    public function initProfiler(MvcEvent $e)
    {
        try {
            $writer = new FirePhp();
            $logger = new Logger();
            $logger->addWriter($writer);
    
            $logger->info('memory usage: ' . memory_get_usage(true) / 1024 / 1024 . 'Mb');
            $logger->info('page execution time: ' . (microtime(true) - APPLICATION_START));
    
            // get sql profiler
            if (null !== ($sqlProfiler = $this->
                    serviceManager->get('Zend\Db\Adapter\Adapter')->getProfiler())) {
    
                $queriesTotalTime = 0;    
                foreach($sqlProfiler->getProfiles() as $query) {
                    $base = array(
                        'time' => $query['elapse'],
                        'query' => $query['sql']
                    );
    
                    $queriesTotalTime += $query['elapse'];
    
                    if(!empty($query['parameters'])) {
                        $params = array();
                        foreach($query['parameters'] as $key => $value) {
                            $params[$key] = $value;
                        }
    
                        $base['params'] = $params;
                    }
    
                    $logger->info('', $base);
                }
    
                $logger->info('sql queries total execution time: '. $queriesTotalTime);
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(\Zend\ModuleManager\ModuleEvent $e)
    {
        // set the service manager
        ApplicationService::setServiceManager($this->serviceManager);

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

        // init layout
        $this->initlayout();
    }

    /**
     * Init session
     */
    protected function initSession()
    {
        try {
            $session = $this->serviceManager->get('Zend\Session\SessionManager');
            $session->start();
    
            $container = new SessionContainer('initialized');
    
            if (!isset($container->init)) {
                $session->regenerateId(true); $container->init = 1;
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init guest identity
     *
     * @param object $authService
     * @return void
     */
    protected function initGuestIdentity($authService)
    {
        try {
            $this->userIdentity = new stdClass();
            $this->userIdentity->role = AclModelBase::DEFAULT_ROLE_GUEST;
            $this->userIdentity->user_id = UserBaseModel::DEFAULT_GUEST_ID;
    
            $request = $this->serviceManager->get('Request');
     
            // get language from cookie
            if (!$request instanceof ConsoleRequest) {
                $this->userIdentity->language = $request->getCookie()->{self::LOCALIZATION_COOKIE};
            }
    
            $authService->getStorage()->write($this->userIdentity);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init identity
     */
    protected function initUserIdentity()
    {
        try {
            $authService = $this->serviceManager->get('Application\AuthService');
    
            // set identity as a site guest
            if (!$authService->hasIdentity()) {
                $this->initGuestIdentity($authService);
            }
            else {
                $this->userIdentity = $authService->getIdentity();
    
                // get extended user info
                if ($authService->getIdentity()->user_id != UserBaseModel::DEFAULT_GUEST_ID) {
                    $user = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('User\Model\Base');
    
                    if (null != ($userInfo = $user->getUserInfo($authService->getIdentity()->user_id))) {
                        // fill the user identity with data
                        foreach($userInfo as $fieldName => $value) {
                            $this->userIdentity->$fieldName = $value;
                        }
                    }
                    else {
                        // user not found, set the current user as a site guest
                        $this->initGuestIdentity($authService);
                    }
                }
            }
    
            // set the user identity
            ApplicationService::setCurrentUserIdentity($this->userIdentity);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init time zone
     */
    protected function initTimeZone()
    {
        try {
            $defaultTimeZone = !empty($this->userIdentity->time_zone)
                ? $this->userIdentity->time_zone
                : ApplicationService::getSetting('application_default_time_zone');
    
            // change time zone settings
            if ($defaultTimeZone != date_default_timezone_get()) {
                date_default_timezone_set($defaultTimeZone);
            }
    
            // get difference to greenwich time (GMT) with colon between hours and minutes
            $date = new DateTime();
    
            $applicationInit = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\Init');
    
            // change time zone settings in model
            $applicationInit->initTimeZone($date->format('P'));
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init php settings
     */
    protected function initPhpSettings()
    {
        try {
            $config = $this->serviceManager->get('Config');
    
            if (!empty($config['php_settings'])) {
                foreach($config['php_settings'] as $settingName => $settingValue) {
                    ini_set($settingName, $settingValue);
                }
            }
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
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
                ->getInstance('Application\Model\Localization');
    
            // init default localization
            $this->localizations = $localization->getAllLocalizations();
            $acceptLanguage = Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'));
    
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
    
            // add a cache for translator
            $translator->setCache($this->serviceManager->get('Cache\Dynamic'));
    
            // init default localization
            Locale::setDefault($this->defaultLocalization['locale']);
    
            AbstractValidator::setDefaultTranslator($translator);
            ApplicationService::setCurrentLocalization($this->defaultLocalization);
            ApplicationService::setLocalizations($this->localizations);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
        }
    }

    /**
     * Init layout
     */
    protected function initlayout()
    {
        try {
            // get a custom template path resolver
            $templatePathResolver = $this->serviceManager->get('customTemplatePathStack');
    
           // replace the default template path stack resolver with custom
           $aggregateResolver = $this->serviceManager->get('Zend\View\Resolver\AggregateResolver');
           $aggregateResolver
                ->attach($templatePathResolver)
                ->getIterator()
                ->remove($this->serviceManager->get('Zend\View\Resolver\TemplatePathStack'));
    
            $layout = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\Layout');
    
            // get default or user defined layouts
            $activeLayouts = !empty($this->userIdentity->layout)
                ? $layout->getLayoutsByName($this->userIdentity->layout)
                : $layout->getDefaultActiveLayouts();
    
            // add layouts paths for each module
            foreach ($this->moduleManager->getModules() as $module) {
                foreach ($activeLayouts as $layoutInfo) {
                    $templatePathResolver->addPath('module/' . $module . '/view/' . $layoutInfo['name']);    
                }
            }
    
            ApplicationService::setCurrentLayouts($activeLayouts);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
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
    
            // get languge param from the route
            if (!$matches->getParam('languge') ||
                        !array_key_exists($matches->getParam('languge'), $this->localizations)) {
    
                // set default language
                $router->setDefaultParam('languge', $this->defaultLocalization['language']);
    
                // remember user's choose language
                $this->setUserLanguage($this->defaultLocalization['language']);
                return;
            }
    
            // init an user localization
            if ($this->defaultLocalization['language'] != $matches->getParam('languge')) {
                $this->serviceManager
                    ->get('translator')
                    ->setLocale($this->localizations[$matches->getParam('languge')]['locale']);
    
                ApplicationService::setCurrentLocalization($this->localizations[$matches->getParam('languge')]);    
            }
    
            Locale::setDefault($this->localizations[$matches->getParam('languge')]['locale']);
            $router->setDefaultParam('languge', $matches->getParam('languge'));
    
            // remember user's choose language
            $this->setUserLanguage($matches->getParam('languge'));
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
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
        if (!$this->userIdentity->language || $this->userIdentity->language != $language) {
            // save language
            if ($this->userIdentity->role != AclModelBase::DEFAULT_ROLE_GUEST) {
                $model = $this->serviceManager
                    ->get('Application\Model\ModelManager')
                    ->getInstance('User\Model\Base')
                    ->setUserLanguage($this->userIdentity->user_id, $language);
            }

            // set language cookie
            $header = new SetCookie();
            $header->setName(self::LOCALIZATION_COOKIE)
                ->setValue($language)
                ->setPath('/')
                ->setExpires(time() + (int) ApplicationService::getSetting('application_localization_cookie_time'));

            $this->serviceManager->get('Response')->getHeaders()->addHeader($header);
            $this->userIdentity->language = $language;
        }
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
                },
                'Cache\Static' => function ($serviceManager)
                {
                    $cache = CacheStorageFactory::factory(array(
                        'adapter' => array(
                            'name' => 'filesystem'
                        ),
                        'plugins' => array(
                            // Don't throw exceptions on cache errors
                            'exception_handler' => array(
                                'throw_exceptions' => false
                            ),
                            'Serializer'
                        )
                    ));
    
                    $cache->setOptions($serviceManager->get('Config')['static_cache']);
                    return $cache;
                },
                'Cache\Dynamic' => function ($serviceManager)
                {
                    // get an active cache engine
                    $cacheEngine = ApplicationService::getSetting('application_dynamic_cache');

                    $cache = CacheStorageFactory::factory(array(
                        'adapter' => array(
                            'name' => $cacheEngine
                        ),
                        'plugins' => array(
                            // Don't throw exceptions on cache errors
                            'exception_handler' => array(
                                'throw_exceptions' => false
                            ),
                            'Serializer'
                        )
                    ));

                    $cacheOptions = array_merge($serviceManager->get('Config')['dynamic_cache'], array(
                        'ttl' => ApplicationService::getSetting('application_dynamic_cache_life_time')
                    ));

                    // add extra options
                    switch ($cacheEngine) {
                        case 'memcached' :
                            $cacheOptions = array_merge($cacheOptions, array(
                                'servers' => array(
                                    ApplicationService::getSetting('application_memcache_host'),
                                    ApplicationService::getSetting('application_memcache_port')
                                )
                            ));
                            break;
                        default :
                    }

                    $cache->setOptions($cacheOptions);
                    return $cache;
                },
                'customTemplatePathStack' => function($serviceManager)
                {
                    return new TemplatePathStack($serviceManager->get('Cache\Dynamic'));
                },
                'Application\Model\ModelManager' => function($serviceManager)
                {
                    return new Model\ModelManager($serviceManager->
                            get('Zend\Db\Adapter\Adapter'), $serviceManager->get('Cache\Static'));
                },
                'Application\Form\FormManager' => function($serviceManager)
                {
                    return new Form\FormManager($serviceManager->get('Translator'));
                },
                'Application\AuthService' => function($serviceManager)
                {
                    $authAdapter = new DbTableAuthAdapter($serviceManager->get('Zend\Db\Adapter\Adapter'), 'user', 'nick_name',
                            'password', 'SHA1(CONCAT(MD5(?), salt)) AND status = "' . UserBaseModel::STATUS_APPROVED . '"');

                    $authService = new AuthenticationService();
                    $authService->setAdapter($authAdapter);

                    return $authService;
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
            'invokables' => array(
                'date' => 'Application\View\Helper\Date',
                'getSetting' => 'Application\View\Helper\Setting',
                'headScript' => 'Application\View\Helper\HeadScript',
                'headlink' => 'Application\View\Helper\HeadLink',
                'isGuest' => 'Application\View\Helper\IsGuest',
                'userIdentity' => 'Application\View\Helper\UserIdentity',
                'checkPermission' => 'Application\View\Helper\CheckPermission',
                'routePermission' => 'Application\View\Helper\RoutePermission',
                'localization' => 'Application\View\Helper\Localization',
                'fileSize' => 'Application\View\Helper\FileSize',
            ),
            'factories' => array(
                'asset' =>  function($serviceManager)
                {
                    return new \Application\View\Helper\Asset($serviceManager->getServiceLocator()->get('Cache\Dynamic'));
                },
                'booleanValue' =>  function($serviceManager)
                {
                    return new \Application\View\Helper\BooleanValue($serviceManager->getServiceLocator()->get('Translator'));
                },
                'adminMenu' =>  function($serviceManager)
                {
                    $adminMenu = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Application\Model\AdminMenu');

                    return new \Application\View\Helper\AdminMenu($adminMenu->getMenu());
                },
                'currentRoute' =>  function($serviceManager)
                {
                    $router = $this->serviceManager->get('router');
                    $request = $this->serviceManager->get('request');
                    $matches = $router->match($request);

                    return new \Application\View\Helper\CurrentRoute($matches, $request->getQuery());
                },
                'flashMessage' => function($serviceManager)
                {
                    $flashmessenger = $serviceManager->getServiceLocator()
                        ->get('ControllerPluginManager')
                        ->get('flashmessenger');
 
                    $messages = new \Application\View\Helper\FlashMessage();
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
