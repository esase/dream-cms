<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
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
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    public $serviceLocator;

    /**
     * Init
     *
     * @param \Zend\ModuleManager\ModuleManagerInterface $moduleManager
     * @return void
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        // clear cache
        $eventManager = AclEvent::getEventManager();
        $eventManager->attach(AclEvent::DELETE_ROLE, function () {
            PageCacheUtility::clearPageCache();
        });

        // clear cache
        $eventManager = LocalizationEvent::getEventManager();
        $eventManager->attach(LocalizationEvent::UNINSTALL, function () {
            PageCacheUtility::clearPageCache();
        });
    }

    /**
     * Return auto loader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
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
     *
     * @return array
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
                'pageRssWidget' => 'Page\View\Widget\PageRssWidget',
                'pageRatingWidget' => 'Page\View\Widget\PageRatingWidget'
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
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}