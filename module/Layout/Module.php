<?php
namespace Layout;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Console\Request as ConsoleRequest;
use Layout\Service\Layout as LayoutService;
use User\Service\UserIdentity as UserIdentityService;
use Layout\View\Resolver\TemplatePathStack;
use Exception;
use Application\Utility\ErrorLogger;

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
     * Init application
     * 
     * @param object $e
     */
    public function initApplication(ModuleEvent $e)
    {
        $request = $this->serviceManager->get('Request');

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
            $templatePathResolver = $this->serviceManager->get('Layout\View\Resolver\TemplatePathStack');

           // replace the default template path stack resolver with one
           $aggregateResolver = $this->serviceManager->get('Zend\View\Resolver\AggregateResolver');
           $aggregateResolver
                ->attach($templatePathResolver)
                ->getIterator()
                ->remove($this->serviceManager->get('Zend\View\Resolver\TemplatePathStack'));

            $layout = $this->serviceManager
                ->get('Application\Model\ModelManager')
                ->getInstance('Layout\Model\Base');

            // get default or user defined layouts
            $activeLayouts = !empty(UserIdentityService::getCurrentUserIdentity()->layout)
                ? $layout->getLayoutsById(UserIdentityService::getCurrentUserIdentity()->layout)
                : $layout->getDefaultActiveLayouts();

            // add layouts paths for each module
            foreach ($this->moduleManager->getModules() as $module) {
                foreach ($activeLayouts as $layoutInfo) {
                    $templatePathResolver->addPath('module/' . $module . '/view/' . $layoutInfo['name']);    
                }
            }

            LayoutService::setCurrentLayouts($activeLayouts);
        }
        catch (Exception $e) {
            ErrorLogger::log($e);
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
                'Layout\View\Resolver\TemplatePathStack' => function($serviceManager) {
                    return new TemplatePathStack($serviceManager->get('Application\Cache\Dynamic'));
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
                    $cache = $this->serviceManager->get('Application\Cache\Dynamic');

                    return new \Layout\View\Helper\LayoutAsset($cache, 
                            LayoutService::getLayoutPath(), LayoutService::getCurrentLayouts(), LayoutService::getLayoutDir());
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