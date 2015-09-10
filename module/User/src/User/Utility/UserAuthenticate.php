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
namespace User\Utility;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Acl\Service\Acl as AclService;
use Acl\Model\AclBase as AclBaseModel;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;

class UserAuthenticate
{
    /**
     * Is authenticate data valid
     *
     * @param string $nickName
     * @param string $password
     * @param array $errors
     * @return boolean|array
     */
    public static function isAuthenticateDataValid($nickName, $password, array &$errors)
    {
        UserIdentityService::getAuthService()
            ->getAdapter()
            ->setIdentity($nickName)
            ->setCredential($password);

        $result = UserIdentityService::getAuthService()->authenticate();

        if (!$result->isValid()) {
            UserEvent::fireLoginFailedEvent(AclBaseModel::DEFAULT_ROLE_GUEST, $nickName);
            $errors = $result->getMessages();

            return false;
        }

        // get the user info
        $userData = UserIdentityService::getAuthService()->getAdapter()->getResultRowObject([
            'user_id',
            'nick_name'
        ]);

        return [
            'user_id' => $userData->user_id,
            'nick_name' => $userData->nick_name
        ];
    }

    /**
     * Login user
     *
     * @param integer $userId
     * @param string $nickName
     * @param boolean $rememberMe
     * @return void
     */
    public static function loginUser($userId,  $nickName, $rememberMe)
    {
        $user = [];
        $user['user_id'] = $userId;

        // save user id
        UserIdentityService::getAuthService()->getStorage()->write($user);
        UserIdentityService::setCurrentUserIdentity(UserIdentityService::getUserInfo($userId));
        AclService::clearCurrentAcl();

        // fire the user login event
        UserEvent::fireLoginEvent($userId, $nickName);

        if ($rememberMe) {
            ServiceLocatorService::getServiceLocator()->
                    get('Zend\Session\SessionManager')->rememberMe((int) SettingService::getSetting('user_session_time'));
        }
    }
}