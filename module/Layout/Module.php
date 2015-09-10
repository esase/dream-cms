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
     * Layout cookie
     */
    CONST LAYOUT_COOKIE = 'layout';

    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Module manager
     *
     * @var \Zend\ModuleManager\ModuleManagerInterface
     */
    protected $moduleManager;

    /**
     * Init
     *
     * @param \Zend\ModuleManager\ModuleManagerInterface
     * @return void
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // get the service manager
        $this->serviceLocator = $moduleManager->getEvent()->getParam('ServiceManager');

        // get the module manager
        $this->moduleManager = $moduleManager;

        $moduleManager->getEventManager()->attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
    }

    /**
     * Init application
     * 
     * @param \Zend\ModuleManager\ModuleEvent $e
     * @return void
     */
    public function initApplication(ModuleEvent $e)
    {
        $request = $this->serviceLocator->get('Request');

        if (!$request instanceof ConsoleRequest) {
            $this->initLayout();
        }
    }

    /**
     * Init layout
     */
    protected function initLayout()
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
     * Get service config
     *
     * @return array
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
     *
     * @return array
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