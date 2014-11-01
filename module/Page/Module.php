<?php
namespace Page;

use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\ModuleManagerInterface;
use Localization\Service\Localization as LocalizationService;

class Module
{
    /**
     * Service locator
     * @var object
     */
    public $serviceLocator;

    /**
     * Init
     *
     * @param object $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');
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
                'Page\Model\PageNestedSet' => function() {
                    return new Model\PageNestedSet(new
                            TableGateway('page_structure', $this->serviceLocator->get('Zend\Db\Adapter\Adapter')));
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
                'pageBreadcrumb' => 'Page\View\Helper\PageBreadcrumb',
                'pageTitle' => 'Page\View\Helper\PageTitle',
                'pagePosition' => 'Page\View\Helper\PagePosition',
                'pageHtmlWidget' => 'Page\View\Widget\PageHtmlWidget',
                'pageSiteMapWidget' => 'Page\View\Widget\PageSiteMapWidget'                
            ],
            'factories' => [
                'pageTree' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageBase');

                    return new \Page\View\Helper\PageTree($model->
                            getPagesTree(LocalizationService::getCurrentLocalization()['language']));
                },
                'pageUserMenu' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageBase');

                    return new \Page\View\Helper\PageUserMenu($model->
                            getUserMenu(LocalizationService::getCurrentLocalization()['language']));
                },
                'pageFooterMenu' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageBase');

                    return new \Page\View\Helper\PageFooterMenu($model->
                            getFooterMenu(LocalizationService::getCurrentLocalization()['language']));
                },
                'pageMenu' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageBase');

                    return new \Page\View\Helper\PageMenu($model->
                            getPagesTree(LocalizationService::getCurrentLocalization()['language']));
                },
                'pageUrl' => function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageBase');

                    return new \Page\View\Helper\PageUrl($model->
                            getPagesMap(), $this->serviceLocator->get('Config')['home_page']);
                },
                'pageInjectWidget' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageWidget');

                    return new \Page\View\Helper\PageInjectWidget($model->
                            getWidgetsConnections(LocalizationService::getCurrentLocalization()['language']));
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