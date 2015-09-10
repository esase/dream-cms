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
namespace User;

use Acl\Event\AclEvent;
use Acl\Model\AclBase as AclBaseModel;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Service\ApplicationTimeZone as TimeZoneService;
use Application\Service\Application as ApplicationService;
use User\Service\UserIdentity as UserIdentityService;
use User\Model\UserBase as UserBaseModel;
use User\Utility\UserCache as UserCacheUtility;
use Localization\Module as LocalizationModule;
use Layout\Event\LayoutEvent;
use Localization\Event\LocalizationEvent;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\ModuleEvent as ModuleEvent;
use Zend\Console\Request as ConsoleRequest;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Exception;
use DateTime;

class Module
{
    /**
     * Service manager
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * User identity
     *
     * @var array
     */
    protected $userIdentity;

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

        $eventManager = AclEvent::getEventManager();
        $eventManager->attach(AclEvent::DELETE_ROLE, function () use ($moduleManager) {
            $userModel = $moduleManager->getEvent()->getParam('ServiceManager')
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserBase');

            // change the empty roles with the default role
            $userModel->updateUsersWithEmptyRoles(AclBaseModel::DEFAULT_ROLE_MEMBER);
        }, -100);
    }

    /**
     * Init application
     * 
     * @param \Zend\ModuleManager\ModuleEvent $e
     * @return void
     */
    public function initApplication(ModuleEvent $e)
    {
        // init user identity
        $this->initUserIdentity();

        // init time zone
        $this->initTimeZone();

        // clear users caches
        $eventManager = LayoutEvent::getEventManager();
        $eventManager->attach(LayoutEvent::UNINSTALL, function () {
            UserCacheUtility::clearUserCache();
        });

        $eventManager = LocalizationEvent::getEventManager();
        $eventManager->attach(LocalizationEvent::UNINSTALL, function () {
            UserCacheUtility::clearUserCache();
        });
    }

    /**
     * Init time zone
     *
     * @return void
     */
    protected function initTimeZone()
    {
        try {
            // get list of all registered time zones
            $registeredTimeZones = TimeZoneService::getTimeZones();

            // what should we use here, user's or default time zone
            $defaultTimeZone = !empty($this->userIdentity['time_zone_name'])
                ? $this->userIdentity['time_zone_name']
                : SettingService::getSetting('application_default_time_zone');

            // check default time zone existing
            if (!in_array($defaultTimeZone, $registeredTimeZones)) {
                $defaultTimeZone = current($registeredTimeZones);
            }

            // change time zone settings
            if ($defaultTimeZone != date_default_timezone_get()) {
                date_default_timezone_set($defaultTimeZone);
            }

            // get difference to greenwich time (GMT) with colon between hours and minutes
            $date = new DateTime;

            $this->serviceLocator
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\ApplicationInit')
                ->setTimeZone($date->format('P'));
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Init identity
     *
     * @return void
     */
    protected function initUserIdentity()
    {
        try {
            $authService = UserIdentityService::getAuthService();

            // set identity as a site guest
            if (!$authService->hasIdentity()) {
                $this->initGuestIdentity($authService);
            }
            else {
                $this->userIdentity = $authService->getIdentity();

                // get extended user info
                if ($authService->getIdentity()['user_id'] != UserBaseModel::DEFAULT_GUEST_ID) {
                    $user = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('User\Model\UserBase');

                    // get user info
                    $userInfo = $user->getUserInfo($authService->getIdentity()['user_id']);

                    if ($userInfo && $userInfo['status'] == UserBaseModel::STATUS_APPROVED) {
                        // fill the user identity with data
                        foreach($userInfo as $fieldName => $value) {
                            $this->userIdentity[$fieldName] = $value;
                        }
                    }
                    else {
                        // user not found, set the current user as a site guest
                        $this->initGuestIdentity($authService);
                    }
                }
            }

            // set the user identity
            UserIdentityService::setCurrentUserIdentity($this->userIdentity);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
    }

    /**
     * Init guest identity
     *
     * @param \Zend\Authentication\AuthenticationService $authService
     * @return void
     */
    protected function initGuestIdentity($authService)
    {
        try {
            $this->userIdentity = [];
            $this->userIdentity['role'] = AclBaseModel::DEFAULT_ROLE_GUEST;
            $this->userIdentity['user_id'] = UserBaseModel::DEFAULT_GUEST_ID;

            $request = $this->serviceLocator->get('Request');

            // get language from cookie
            if (!$request instanceof ConsoleRequest) {
                $this->userIdentity['language'] = isset($request->getCookie()->{LocalizationModule::LOCALIZATION_COOKIE})
                    ? $request->getCookie()->{LocalizationModule::LOCALIZATION_COOKIE}
                    : null;
            }

            $authService->getStorage()->write($this->userIdentity);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
        }
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
                'User\AuthService' => function() {
                    $adapter = $this->serviceLocator->get('Zend\Db\Adapter\Adapter');
                    $authAdapter = new DbTableAuthAdapter($adapter, 'user_list', 
                            'nick_name', 'password', 'SHA1(CONCAT(MD5(?), "' . $this->serviceLocator->get('Config')['site_salt'] . '"))');

                    $select = $authAdapter->getDbSelect();
                    $select->where([
                        'status' =>  UserBaseModel::STATUS_APPROVED
                    ]);

                    $authService = new AuthenticationService();
                    $authService->setAdapter($authAdapter);

                    return $authService;
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
                'userLoginWidget' => 'User\View\Widget\UserLoginWidget',
                'userRegisterWidget' => 'User\View\Widget\UserRegisterWidget',
                'userActivateWidget' => 'User\View\Widget\UserActivateWidget',
                'userForgotWidget' => 'User\View\Widget\UserForgotWidget',
                'userPasswordResetWidget' => 'User\View\Widget\UserPasswordResetWidget',
                'userDeleteWidget' => 'User\View\Widget\UserDeleteWidget',
                'userInfoWidget' => 'User\View\Widget\UserInfoWidget',
                'userAvatarWidget' => 'User\View\Widget\UserAvatarWidget',
                'userDashboardWidget' => 'User\View\Widget\UserDashboardWidget',
                'userDashboardUserInfoWidget' => 'User\View\Widget\UserDashboardUserInfoWidget',
                'userEditWidget' => 'User\View\Widget\UserEditWidget',
                'userDashboardAdministrationWidget' => 'User\View\Widget\UserDashboardAdministrationWidget'
            ],
            'factories' => [
                'userAvatarUrl' => function(){
                    $thumbDir  = ApplicationService::getResourcesUrl() . UserBaseModel::getThumbnailsDir();
                    $avatarDir = ApplicationService::getResourcesUrl() . UserBaseModel::getAvatarsDir();

                    return new \User\View\Helper\UserAvatarUrl($thumbDir, $avatarDir);
                },
                'userMenu' => function() {
                    $userMenu = $this->serviceLocator
                        ->get('Application\Model\ModelManager')
                        ->getInstance('User\Model\UserMenu');

                    return new \User\View\Helper\UserMenu($userMenu->getMenu());
                },
                'userIdentity' => function() {
                    return new \User\View\Helper\UserIdentity(UserIdentityService::getCurrentUserIdentity());
                },
                'userIsGuest' => function() {
                    return new \User\View\Helper\UserIsGuest(UserIdentityService::isGuest());
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