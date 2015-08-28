<?php

namespace Page;

use Acl\Event\AclEvent;
use Page\Utility\PageCache as PageCacheUtility;
use Localization\Service\Localization as LocalizationService;
use Localization\Event\LocalizationEvent;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\ModuleManagerInterface;

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
     * @param \Zend\ModuleManager\ModuleManagerInterface $moduleManager
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        // clear cache
        $eventManager = AclEvent::getEventManager();
        $eventManager->attach(AclEvent::DELETE_ROLE, function () use ($moduleManager) {
            PageCacheUtility::clearPageCache();
        });

        // clear cache
        $eventManager = LocalizationEvent::getEventManager();
        $eventManager->attach(LocalizationEvent::UNINSTALL, function () {
            PageCacheUtility::clearPageCache();
        });
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
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                ]
            ]
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
                'pageXmlSiteMap' => 'Page\View\Helper\PageXmlSiteMap',
                'page404' => 'Page\View\Helper\Page404',
                'pageBreadcrumb' => 'Page\View\Helper\PageBreadcrumb',
                'pageTitle' => 'Page\View\Helper\PageTitle',
                'pageWidgetTitle' => 'Page\View\Helper\PageWidgetTitle',
                'pagePosition' => 'Page\View\Helper\PagePosition',
                'pageHtmlWidget' => 'Page\View\Widget\PageHtmlWidget',
                'pageSiteMapWidget' => 'Page\View\Widget\PageSiteMapWidget',
                'pageContactFormWidget' => 'Page\View\Widget\PageContactFormWidget',
                'pageSidebarMenuWidget' => 'Page\View\Widget\PageSidebarMenuWidget',
                'pageShareButtonsWidget' => 'Page\View\Widget\PageShareButtonsWidget',
                'pageRssWidget' => 'Page\View\Widget\PageRssWidget'
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

                    return new \Page\View\Helper\PageUrl($this->
                            serviceLocator->get('Config')['home_page'], $model->getPagesMap());
                },
                'pageInjectWidget' =>  function() {
                    $model = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Page\Model\PageWidget');

                    return new \Page\View\Helper\PageInjectWidget($model->
                            getWidgetsConnections(LocalizationService::getCurrentLocalization()['language']));
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