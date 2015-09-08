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
namespace Install;

use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mvc\MvcEvent as ModuleEvent;
use Zend\Validator\AbstractValidator;
use Zend\Mvc\MvcEvent;
use Locale;

class Module
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * List of registered localizations
     *
     * @var array
     */
    protected static $localizations;

    /**
     * Default localization
     *
     * @var array
     */
    protected static $defaultLocalization;

    /**
     * Current localization
     *
     * @var array
     */
    protected static $currentLocalization;

    /**
     * Is Intl loaded
     *
     * @var boolean
     */
    protected $intlLoaded;

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

        $moduleManager->getEventManager()->
                attach(ModuleEvent::EVENT_LOAD_MODULES_POST, [$this, 'initApplication']);
    }

    /**
     * Bootstrap
     *
     * @param \Zend\Mvc\MvcEvent $e
     * @return void
     */
    public function onBootstrap(MvcEvent $e)
    {
        // init user localization
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, [
            $this, 'initUserLocalization'
        ], 100);
    }

    /**
     * Init application
     * 
     * @param \Zend\Mvc\MvcEvent $e
     * @return void
     */
    public function initApplication(ModuleEvent $e)
    {
        $this->intlLoaded = extension_loaded('intl');

        // init php settings
        $this->initPhpSettings();

        // init default localization
        $this->initDefaultLocalization();
    }

    /**
     * Init default localization
     */
    protected function initDefaultLocalization()
    {
        self::$localizations = $this->serviceLocator->get('config')['install_languages'];
        $this->serviceLocator->get('translator');

        $acceptLanguage = $this->intlLoaded
            ? Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'))
            : null;

        $defaultLanguage = $acceptLanguage 
            ? substr($acceptLanguage, 0, 2) 
            : null;

        // setup locale
        self::$defaultLocalization =  array_key_exists($defaultLanguage, self::$localizations)
            ? self::$localizations[$defaultLanguage]
            : current(self::$localizations);

        // init translator settings
        $translator = $this->serviceLocator->get('translator');
        $translator->setLocale(self::$defaultLocalization['locale']);

        // init default localization
        if ($this->intlLoaded) {
            Locale::setDefault(self::$defaultLocalization['locale']);
        }

        AbstractValidator::setDefaultTranslator($translator);
        self::$currentLocalization = self::$defaultLocalization;
    }

    /**
     * Init user localization
     *
     * @param \Zend\Mvc\MvcEvent $e
     * @return void
     */
    public function initUserLocalization(MvcEvent $e)
    {
        // get a router
        $router = $this->serviceLocator->get('router');
        $matches = $e->getRouteMatch();

        if (!$matches->getParam('language') 
                || !array_key_exists($matches->getParam('language'), self::$localizations)) {

            if (!$matches->getParam('language')) {
                // set default language
                $router->setDefaultParam('language', self::$defaultLocalization['language']);

                return;
            }

            // show a 404 page
            $matches->setParam('action', 'not-found');

            return;
        }

        // init an user localization
        if (self::$defaultLocalization['language'] != $matches->getParam('language')) {
            $this->serviceLocator
                ->get('translator')
                ->setLocale(self::$localizations[$matches->getParam('language')]['locale']);

            self::$currentLocalization = self::$localizations[$matches->getParam('language')];
        }

        if ($this->intlLoaded) {
            Locale::setDefault(self::$localizations[$matches->getParam('language')]['locale']);
        }

        $router->setDefaultParam('language', $matches->getParam('language'));
    }

    /**
     * Init php settings
     *
     * @return void
     */
    protected function initPhpSettings()
    {
        $config = $this->serviceLocator->get('Config');

        if (!empty($config['php_settings'])) {
            foreach($config['php_settings'] as $settingName => $settingValue) {
                ini_set($settingName, $settingValue);
            }
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
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Install\Model\InstallBase' => function() {
                    return new Model\InstallBase();
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
            'factories' => [
                'config' => function() {
                    return new \Install\View\Helper\Config($this->serviceLocator->get('config'));
                },
                'localization' => function() {
                    return new \Install\View\Helper\Localization(self::$currentLocalization, self::$localizations);
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