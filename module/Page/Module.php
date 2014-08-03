<?php
namespace Page;

use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\ModuleManagerInterface;

use Page\View\Helper\InjectWidget;

class Module
{
    /**
     * Service manager
     * @var object
     */
    public $serviceManager;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceManager = $moduleManager->getEvent()->getParam('ServiceManager');
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
                'Page\Model\Page' => function($serviceManager) {
                    return new Model\Page(new TableGateway('page_structure', $serviceManager->get('Zend\Db\Adapter\Adapter')));
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
                'pageTitle' => 'Page\View\Helper\PageTitle'
            ],
            'factories' => [
                'injectWidget' =>  function() {
                    $widget = $this->serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\Widget');

                    return new InjectWidget($widget->getWidgetsConnections());
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