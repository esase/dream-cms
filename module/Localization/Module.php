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
namespace Localization;

use Localization\Service\Localization as LocalizationService;
use Acl\Model\AclBase as AclBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use User\Service\UserIdentity as UserIdentityService;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Console\Request as ConsoleRequest;
use Zend\Validator\AbstractValidator;
use Zend\Mvc\MvcEvent;
use Zend\Http\Header\SetCookie;
use Exception;
use Locale;

class Module
{
    /**
     * Localization cookie
     */
    CONST LOCALIZATION_COOKIE = 'language';

    /**
     * User identity
     *
     * @var array
     */
    protected $userIdentity;

    /**
     * List of registered localizations
     *
     * @var array
     */
    protected $localizations;

    /**
     * Default localization
     *
     * @var array
     */
    protected $defaultLocalization;

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
        $request = $this->serviceLocator->get('Request');

        if (!$request instanceof ConsoleRequest) {
            // init user localization
            $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, [
                $this, 'initUserLocalization'
            ], 100);
        }
    }

    /**
     * Init application
     * 
     * @param \Zend\ModuleManager\ModuleEvent $e
     * @return void
     */
    public function initApplication(ModuleEvent $e)
    {
        $this->userIdentity = UserIdentityService::getCurrentUserIdentity();

        // init default localization
        $this->initDefaultLocalization();
    }

    /**
     * Init default localization
     *
     * @return void
     */
    private function initDefaultLocalization()
    {
        try {
            // get all registered localizations
            $localization = $this->serviceLocator
                ->get('Application\Model\ModelManager')
                ->getInstance('Localization\Model\LocalizationBase');

            // init default localization
            $this->localizations = $localization->getAllLocalizations();
            $acceptLanguage = Locale::acceptFromHttp(getEnv('HTTP_ACCEPT_LANGUAGE'));

            $defaultLanguage = !empty($this->userIdentity['language'])
                ? $this->userIdentity['language']
                : ($acceptLanguage ? substr($acceptLanguage, 0, 2) : null);

            // setup locale
            $this->defaultLocalization =  array_key_exists($defaultLanguage, $this->localizations)
                ? $this->localizations[$defaultLanguage]
                : current($this->localizations);

            // init translator settings
            $translator = $this->serviceLocator->get('translator');
            $translator->setLocale($this->defaultLocalization['locale']);

            // add a cache for translator
            $request = $this->serviceLocator->get('Request');

            if (!$request instanceof ConsoleRequest) {
                $translator->setCache($this->serviceLocator->get('Application\Cache\Dynamic'));
            }

            // init default localization
            Locale::setDefault($this->defaultLocalization['locale']);

            AbstractValidator::setDefaultTranslator($translator);
            LocalizationService::setCurrentLocalization($this->defaultLocalization);
            LocalizationService::setLocalizations($this->localizations);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Init user localization
     *
     * @param \Zend\Mvc\MvcEvent $e
     * @return void
     */
    public function initUserLocalization(MvcEvent $e)
    {
        try {
            // get a router
            $router = $this->serviceLocator->get('router');
            $matches = $e->getRouteMatch();

            if (!$matches->getParam('language') 
                    || !array_key_exists($matches->getParam('language'), $this->localizations)) {

                if (!$matches->getParam('language')) {
                    // set default language
                    $router->setDefaultParam('language', $this->defaultLocalization['language']);

                    // remember user's chose language
                    $this->setUserLanguage($this->defaultLocalization['language']);

                    return;
                }

                // show a 404 page
                $matches->setParam('action', 'not-found');
                return;
            }

            // init an user localization
            if ($this->defaultLocalization['language'] != $matches->getParam('language')) {
                $this->serviceLocator
                    ->get('translator')
                    ->setLocale($this->localizations[$matches->getParam('language')]['locale']);

                LocalizationService::setCurrentLocalization($this->localizations[$matches->getParam('language')]);    
            }

            Locale::setDefault($this->localizations[$matches->getParam('language')]['locale']);
            $router->setDefaultParam('language', $matches->getParam('language'));

            // remember user's choose language
            $this->setUserLanguage($matches->getParam('language'));
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Set user's language
     *
     * @param string $language
     * @return void
     */
    protected function setUserLanguage($language)
    {
        if (!$this->userIdentity['language'] || $this->userIdentity['language'] != $language) {
            // save language
            if ($this->userIdentity['role'] != AclBaseModel::DEFAULT_ROLE_GUEST) {
                 $this->serviceLocator
                    ->get('Application\Model\ModelManager')
                    ->getInstance('User\Model\UserBase')
                    ->setUserLanguage($this->userIdentity['user_id'], $language);
            }

            // set language cookie
            $header = new SetCookie();
            $header->setName(self::LOCALIZATION_COOKIE)
                ->setValue($language)
                ->setPath('/')
                ->setExpires(time() + (int) SettingService::getSetting('application_localization_cookie_time'));

            $this->serviceLocator->get('Response')->getHeaders()->addHeader($header);
            $this->userIdentity['language'] = $language;

            // change globally user's identity
            UserIdentityService::setCurrentUserIdentity($this->userIdentity);
            UserIdentityService::getAuthService()->getStorage()->write($this->userIdentity);
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
                'localization' => function() {
                    return new \Localization\View\Helper\
                            Localization(LocalizationService::getCurrentLocalization(), LocalizationService::getLocalizations());
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