<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use stdClass;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Validator\AbstractValidator;

use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Storage;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

use Zend\Session\SessionManager;
use Zend\Session\Container as SessionContainer;

class Module
{
    /**
     * List of registered localizations
     * @var array
     */
    private $localizations;

    /**
     * Default localization
     * @var array
     */
    private $defaultLocalization;

    /**
     * Service manager
     * @var object
     */
    private $serviceManager;

    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $this->serviceManager = $e->getApplication()->getServiceManager();

        // init session
        $this->initSession();

        // init default localization 
        $this->initDefaultLocalization($eventManager);

        // init acl
        $this->initAcl();

        // init identity
        $this->initIdentity();
    }

    /**
     * Init session 
     */
    private function initSession()
    {
        $session = $this->serviceManager->get('Zend\Session\SessionManager');
        $session->start();

        $container = new SessionContainer('initialized');

        if (!isset($container->init)) {
            $session->regenerateId(true);
            $container->init = 1;
        }
    }

    /**
     * Init default localization 
     *
     * @param object $eventManager
     */
    private function initDefaultLocalization($eventManager)
    {
        // get all registered localizations
        $localization = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Localization');

        // init default localization
        if (null != ($this->localizations = $localization->getAllLocalizations())) {
            if (null != ($defaultLanguage = \Locale::acceptFromHttp($this->serviceManager->
                    get('request')->getServer('HTTP_ACCEPT_LANGUAGE')))) {

                // extract language code from locale
                $defaultLanguage = substr($defaultLanguage, 0, 2);
            }

            if ($defaultLanguage && array_key_exists($defaultLanguage, $this->
                    localizations)) {

                $this->defaultLocalization = $this->localizations[$defaultLanguage];
            }
            else {
                $this->defaultLocalization = current($this->localizations);
            }

            $translator = $this->serviceManager->get('translator');
            $translator->setLocale($this->defaultLocalization['locale']);
            $translator->setCache($this->serviceManager->get('Cache\Dynamic'));
            AbstractValidator::setDefaultTranslator($translator);
        }

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array(
            $this, 'initUserLocalization'
        ), 100);
    }

    /**
     * Init acl
     */
    private function initAcl()
    {
        $acl = $this->serviceManager->get('Application\Acl');
        $aclModel = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Acl');

        // get all roles
        if (null != ($roles = $aclModel->getAllRoles())) {
            foreach ($roles as $roleId => $roleInfo) {
                $acl->addRole(new Role($roleId));
            }
        }
        
       // echo '<pre>';
        //print_r($acl->getRoles());
        //exit;
            /*
            $acl = new Zend_Acl();

        $acl->addRole(new Zend_Acl_Role(ACL_ROLE_GUEST));
        $acl->addRole(new Zend_Acl_Role(ACL_ROLE_MEMBER));
        $acl->addRole(new Zend_Acl_Role(ACL_ROLE_ADMIN));

        $acl->deny(array(
            ACL_ROLE_GUEST,
            ACL_ROLE_MEMBER
        ));
        $acl->allow(ACL_ROLE_ADMIN);//admin can do everything

        Zend_Registry::set('Zend_Acl', $acl);
            */
            
            //1. register all roles
            //2. register all resources
            //3. 
    /*
            $localization = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Localization');
    */
        //$acl = $this->serviceManager->get('Application\Model\Acl');
    }

    /**
     * Init identity
     */
    private function initIdentity()
    {
        $authService = $this->serviceManager->get('Application\AuthService');

        if (!$authService->hasIdentity()) {
            $user = new stdClass();
            $user->role = \Application\Model\Acl::DEFAULT_ROLE_GUEST;
            $user->user_id = -1;

            $authService->getStorage()->write($user);
        }
    }

    /**
     * Init user localization
     *
     * @param object $e MvcEvent
     */
    public function initUserLocalization(MvcEvent $e)
    { 
        if ($this->localizations) {
            $router = $this->serviceManager->get('router');
            $matches = $e->getRouteMatch();

            // get languge param from the route
            if (!array_key_exists($matches->getParam('languge'), $this->localizations)) {
                $router->setDefaultParam('languge', $this->defaultLocalization['language']);
                return;
            }

            // init user localization
            $translator = $this->serviceManager->get('translator');
            $translator->setLocale($this->localizations[$matches->getParam('languge')]['locale']);
            $router->setDefaultParam('languge', $matches->getParam('languge'));
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
        return  array(
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
                    $cache = \Zend\Cache\StorageFactory::factory(array(
                        'adapter' => array(
                            'name' => $config['static_cache']['type'],
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
                            'name' => $config['dynamic_cache']['type'],
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
                    $sessionConfig = new $config['session']['config']['class']();
                    $sessionConfig->setOptions($config['session']['config']['options']);

                    // get session storage
                    $sessionStorage = new $config['session']['storage']();

                    $sessionSaveHandler = null;
                    if (!empty($config['session']['save_handler'])) {
                        // class should be fetched from service manager since it will require constructor arguments
                        $sessionSaveHandler = $serviceManager->get($config['session']['save_handler']);
                    }

                    // get session manager
                    $sessionManager = new SessionManager($sessionConfig, $sessionStorage, $sessionSaveHandler);
                    
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
            ),
        );
    }

    /**
     * Get autoloader config
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
