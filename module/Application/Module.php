<?php
namespace Application;

use Application\Service\ServiceManager as ServiceManagerService;
use Application\Utility\ErrorLogger;
use Application\Service\Setting as SettingService;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Mvc\MvcEvent;
use Zend\Log\Writer\FirePhp as FirePhp;
use Zend\Log\Logger as Logger;
use Zend\Cache\StorageFactory as CacheStorageFactory;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Session\SessionManager;
use Zend\Session\Container as SessionContainer;
use Zend\Console\Request as ConsoleRequest;
use Exception;

class Module
{
    /**
     * Service managerzend
     * @var object
     */
    protected $serviceManager;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
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

        $request = $this->serviceManager->get('Request');

        if (!$request instanceof ConsoleRequest) {
            // init profiler
            $config = $this->serviceManager->get('Config');
            if ($config['profiler']) {
                $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_FINISH, [
                    $this, 'initProfiler'
                ]);
            }
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
                    $base = [
                        'time' => $query['elapse'],
                        'query' => $query['sql']
                    ];

                    $queriesTotalTime += $query['elapse'];

                    if(!empty($query['parameters'])) {
                        $params = [];
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
    public function initApplication(ModuleEvent $e)
    {
        // set the service manager
        ServiceManagerService::setServiceManager($this->serviceManager);

        $request = $this->serviceManager->get('Request');

        if (!$request instanceof ConsoleRequest) {
            // init session
            $this->initSession();
        }

        // init php settings
        $this->initPhpSettings();
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
        return [
            'factories' => [
                'Zend\Session\SessionManager' => function ($serviceManager) {
                    // get session config
                    $config = $serviceManager->get('config');
                    $sessionConfig = new $config['session']['config']['class']();
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
                            $chain->attach('session.validate', [new $validator(), 'isValid']);
                        }
                    }

                    SessionContainer::setDefaultManager($sessionManager);
                    return $sessionManager;
                },
                'Application\Cache\Static' => function ($serviceManager) {
                    $cache = CacheStorageFactory::factory([
                        'adapter' => [
                            'name' => 'filesystem'
                        ],
                        'plugins' => [
                            // Don't throw exceptions on cache errors
                            'exception_handler' => [
                                'throw_exceptions' => false
                            ],
                            'Serializer'
                        ]
                    ]);
    
                    $cache->setOptions($serviceManager->get('Config')['static_cache']);
                    return $cache;
                },
                'Application\Cache\Dynamic' => function ($serviceManager) {
                    // get an active cache engine
                    $cacheEngine = SettingService::getSetting('application_dynamic_cache');

                    $cache = CacheStorageFactory::factory([
                        'adapter' => [
                            'name' => $cacheEngine
                        ],
                        'plugins' => [
                            // Don't throw exceptions on cache errors
                            'exception_handler' => [
                                'throw_exceptions' => false
                            ],
                            'Serializer'
                        ]
                    ]);

                    $cacheOptions = array_merge($serviceManager->get('Config')['dynamic_cache'], [
                        'ttl' => SettingService::getSetting('application_dynamic_cache_life_time')
                    ]);

                    // add extra options
                    switch ($cacheEngine) {
                        case 'memcached' :
                            $cacheOptions = array_merge($cacheOptions, [
                                'servers' => [
                                    SettingService::getSetting('application_memcache_host'),
                                    SettingService::getSetting('application_memcache_port')
                                ]
                            ]);
                            break;
                        default :
                    }

                    $cache->setOptions($cacheOptions);
                    return $cache;
                },
                'Application\Model\ModelManager' => function($serviceManager) {
                    return new Model\ModelManager($serviceManager->
                            get('Zend\Db\Adapter\Adapter'), $serviceManager->get('Application\Cache\Static'), $serviceManager);
                },
                'Application\Form\FormManager' => function($serviceManager) {
                    return new Form\FormManager($serviceManager->get('Translator'));
                }
            ]
        ];
    }

    /** 
     * Get view helper config
     */
    public function getViewHelperConfig()
    {
        return [
            'invokables' => [
                'applicationSetting' => 'Application\View\Helper\ApplicationSetting',
                'applicationRoute' => 'Application\View\Helper\ApplicationRoute',
                'applicationRandId' => 'Application\View\Helper\ApplicationRandId',
                'applicationDate' => 'Application\View\Helper\ApplicationDate'
            ],
            'factories' => [
                'applicationFlashMessage' => function() {
                    $flashmessenger = $this->serviceManager
                        ->get('ControllerPluginManager')
                        ->get('flashmessenger');
 
                    $messages = new \Application\View\Helper\ApplicationFlashMessage();
                    $messages->setFlashMessenger($flashmessenger);
 
                    return $messages;
                }
            ]
        ];
    }

    /**
     * Get autoloader config
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
    }
}