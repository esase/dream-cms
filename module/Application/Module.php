<?php

namespace Application;

use Zend\ModuleManager\ModuleEvent as ModuleEvent;

use Application\Model\Acl as AclModel;
use Application\Service\Service as ApplicationService;

use StdClass;
use DateTime;

use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

use Zend\Session\Container as SessionContainer;

use Zend\Validator\AbstractValidator;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Permissions\Acl\Acl as Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

use Zend\Log\Writer\FirePhp as FirePhp;
use Zend\Log\Logger as Logger;

class Module
{
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
     * Init
     *
     * @param object $moduleManager
     */
    function init(\Zend\ModuleManager\ModuleManager $moduleManager)
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
        // init user localization
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'initUserLocalization'
        ), 100);

        $config = $this->serviceManager->get('Config');

        // init profiler
        if ($config['profiler']) {
            $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_FINISH, array(
                $this, 'initProfiler'
            ));
        }
    }

    /**
     * Init profiler
     */
    public function initProfiler(MvcEvent $e)
    {
        $writer = new FirePhp();
        $logger = new Logger();
        $logger->addWriter($writer);

        $logger->info('memory usage: ' . round(memory_get_usage(true) / 1024) . 'Mb');
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

        // init acl
        $this->initAcl();
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
     * Init guest identity
     *
     * @param object $authService
     * @return void
     */
    protected function initGuestIdentity($authService)
    {
        $this->userIdentity = new stdClass();
        $this->userIdentity->role = AclModel::DEFAULT_ROLE_GUEST;
        $this->userIdentity->user_id = AclModel::DEFAULT_GUEST_ID;

        $authService->getStorage()->write($this->userIdentity);
    }

    /**
     * Init identity
     */
    protected function initUserIdentity()
    {
        $authService = $this->serviceManager->get('Application\AuthService');

        // set identity as a site guest
        if (!$authService->hasIdentity()) {
            $this->initGuestIdentity($authService);
        }
        else {
            $this->userIdentity = $authService->getIdentity();

            // get extended user info
            if ($authService->getIdentity()->user_id != AclModel::DEFAULT_GUEST_ID) {
                $user = $this->serviceManager
                    ->get('Application\Model\ModelManager')
                    ->getInstance('Users\Model\Base');

                if (null != ($userInfo = $user->
                        getUserInfoById($authService->getIdentity()->user_id))) {

                    // fill user identity with user data
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
            ->get('Application\Model\ModelManager')
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
            ->get('Application\Model\ModelManager')
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
        ApplicationService::setCurrentLocalization($this->defaultLocalization);
    }

    /**
     * Init layout
     */
    protected function initlayout()
    {
        $templatePathResolver = $this->serviceManager->get('Zend\View\Resolver\TemplatePathStack');

        $layout = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\Layout');

        // get default or user defined layouts
        $activeLayouts = !empty($this->userIdentity->layout)
            ? $layout->getLayoutsByName($this->userIdentity->layout)
            : $layout->getDefaultActiveLayouts();

        // add layouts paths    
        foreach ($this->moduleManager->getModules() as $module) {
            foreach ($activeLayouts as $layoutInfo) {
                $templatePathResolver->addPath('module/' . $module . '/view/' . $layoutInfo['name']);    
            }
        }

        // process template map
        $templatePathResolver = $this->serviceManager->get('Zend\View\Resolver\TemplateMapResolver');
        $templateMap = array();
        $activeLayouts = array_reverse($activeLayouts);

        foreach ($templatePathResolver as $name => $path) {
            foreach ($activeLayouts as $layoutInfo) {
                $filePath = sprintf($path, $layoutInfo['name']);

                // replace special path marker with current layout name
                if (file_exists($filePath)) {
                    $templateMap[$name] = $filePath;
                    break;
                }
            }
        }

        if ($templateMap) {
            $templatePathResolver->setMap($templateMap);
        }

        ApplicationService::setCurrentLayouts($activeLayouts);
    }

    /**
     * init acl
     */ 
    protected function initAcl()
    {
        // admin can do everything
        if ($this->userIdentity->role == AclModel::DEFAULT_ROLE_ADMIN) {
            return;
        }

        $acl = new Acl();
        $acl->addRole(new Role($this->userIdentity->role));

        $aclModel = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\Acl');

        // get acl resources
        if (null != ($resources = $aclModel->
                getAclResources($this->userIdentity->role, $this->userIdentity->user_id))) {

            // process acl resources
            $resourcesInfo = array();
            foreach ($resources as $resource) {
                // add new resource
                $acl->addResource(new Resource($resource['resource']));

                // add resource's action
                $resource['permission'] == AclModel::ACTION_ALLOWED
                    ? $acl->allow($this->userIdentity->role, $resource['resource'])
                    : $acl->deny($this->userIdentity->role, $resource['resource']);

                $resourcesInfo[$resource['resource']] = $resource;
            }

            ApplicationService::setCurrentAclResources($resourcesInfo);
        };

        ApplicationService::setCurrentAcl($acl);
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

            ApplicationService::setCurrentLocalization($this->localizations[$matches->getParam('languge')]);    
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
                'Application\Model\ModelManager' => function($serviceManager)
                {
                    return new Model\ModelManager($serviceManager);
                },
                'Application\AuthService' => function($serviceManager)
                {
                    $authAdapter = new DbTableAuthAdapter($serviceManager->get('Zend\Db\Adapter\Adapter'),
                            'users', 'nick_name', 'password', 'SHA1(CONCAT(MD5(?), salt))');

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
                'getSetting' => 'Application\View\Helper\Setting',
                'asset' => 'Application\View\Helper\Asset',
                'headScript' => 'Application\View\Helper\HeadScript',
                'headlink' => 'Application\View\Helper\HeadLink'
            ),
            'factories' => array(
                'flashMessages' => function($serviceManager)
                {
                    $flashmessenger = $serviceManager->getServiceLocator()
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
