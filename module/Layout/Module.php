<?php
namespace Layout;

use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Layout\Service\Layout as LayoutService;
use User\Service\UserIdentity as UserIdentityService;
use Layout\View\Resolver\TemplatePathStack;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Console\Request as ConsoleRequest;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Exception;

class Module
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Module manager
     * @var object
     */
    protected $moduleManager;

    /**
     * Layout cookie
     */ 
    CONST LAYOUT_COOKIE = 'layout';

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get the service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        // get the module manager
        $this->moduleManager = $moduleManager;

        $moduleManager->getEventManager()->
            attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
    }

    /**
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(ModuleEvent $e)
    {
        $request = $this->serviceLocator->get('Request');

        if (!$request instanceof ConsoleRequest) {
            $this->initlayout();
        }
    }

    /**
     * Init layout
     */
    protected function initlayout()
    {
        try {
            // get a custom template path resolver
            $templatePathResolver = $this->serviceLocator->get('Layout\View\Resolver\TemplatePathStack');

           // replace the default template path stack resolver with one
           $aggregateResolver = $this->serviceLocator->get('Zend\View\Resolver\AggregateResolver');
           $aggregateResolver
                ->attach($templatePathResolver)
                ->getIterator()
                ->remove($this->serviceLocator->get('Zend\View\Resolver\TemplatePathStack'));

            $layout = $this->serviceLocator
                ->get('Application\Model\ModelManager')
                ->getInstance('Layout\Model\LayoutBase');

            $request = $this->serviceLocator->get('Request');

            // get a layout from cookies
            $allowSelectLayouts = (int) SettingService::getSetting('layout_select');
            $cookieLayout = isset($request->getCookie()->{self::LAYOUT_COOKIE}) && $allowSelectLayouts
                ? (int) $request->getCookie()->{self::LAYOUT_COOKIE}
                : null;

            // init a user selected layout
            if ($cookieLayout) {
                $activeLayouts = $layout->getLayoutsById($cookieLayout);
            }
            else {
                $activeLayouts = !empty(UserIdentityService::getCurrentUserIdentity()['layout']) && $allowSelectLayouts
                    ? $layout->getLayoutsById(UserIdentityService::getCurrentUserIdentity()['layout']) 
                    : $layout->getDefaultActiveLayouts();
            }

            // add layouts paths for each module
            foreach ($this->moduleManager->getModules() as $module) {
                foreach ($activeLayouts as $layoutInfo) {
                    $templatePathResolver->addPath('module/' . $module . '/view/' . $layoutInfo['name']);    
                }
            }

            LayoutService::setCurrentLayouts($activeLayouts);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
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
            'factories' => [
                'Layout\View\Resolver\TemplatePathStack' => function() {
                    return new TemplatePathStack($this->serviceLocator->get('Application\Cache\Dynamic'));
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
            'invokables' => [
                'layoutHeadLink' => 'Layout\View\Helper\LayoutHeadLink',
                'layoutHeadScript' => 'Layout\View\Helper\LayoutHeadScript'
            ],
            'factories' => [
                'layoutAsset' =>  function() {
                    $cache = $this->serviceLocator->get('Application\Cache\Dynamic');

                    return new \Layout\View\Helper\LayoutAsset($cache, 
                            LayoutService::getLayoutPath(), LayoutService::getCurrentLayouts(), LayoutService::getLayoutDir());
                },
                'layoutList' =>  function() {
                    $layouts = $this->serviceLocator
                            ->get('Application\Model\ModelManager')
                            ->getInstance('Layout\Model\LayoutBase');

                    return new \Layout\View\Helper\LayoutList($layouts->getAllInstalledLayouts(), LayoutService::getCurrentLayouts());
                },
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