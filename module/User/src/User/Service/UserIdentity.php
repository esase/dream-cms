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
namespace User\Service;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use User\Model\UserBase as UserBaseModel;

class UserIdentity
{
    /**
     * Current user identity
     *
     * @var array
     */
    protected static $currentUserIdentity;

    /**
     * Auth service
     *
     * @var \Zend\Authentication\AuthenticationService
     */
    protected static $authService;

    /**
     * Get auth service
     *
     * @return \Zend\Authentication\AuthenticationService
     */
    public static function getAuthService()
    {
        if (!self::$authService) {
            self::$authService = ServiceLocatorService::getServiceLocator()->get('User\AuthService');
        }

        return self::$authService;
    }

    /**
     * Set current user identity
     *
     * @param array $userIdentity
     * @return void
     */
    public static function setCurrentUserIdentity(array $userIdentity)
    {
        self::$currentUserIdentity = $userIdentity;
    }

    /**
     * Get current user identity
     *
     * @return array
     */
    public static function getCurrentUserIdentity()
    {
        return self::$currentUserIdentity;
    }

    /**
     * Get user info
     *
     * @param integer $userId
     * @param string $field
     * @return array
     */
    public static function getUserInfo($userId, $field = null)
    {
        return ServiceLocatorService::getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\UserBase')
            ->getUserInfo($userId, $field);
    }

    /**
     * Check is default user or not
     *
     * @return boolean
     */
    public static function isDefaultUser()
    {
        return self::getCurrentUserIdentity()['user_id'] == UserBaseModel::DEFAULT_USER_ID;
    }

    /**
     * Check is guest or not
     *
     * @return boolean
     */
    public static function isGuest()
    {
        return self::getCurrentUserIdentity()['user_id'] == UserBaseModel::DEFAULT_GUEST_ID;
    }
}