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
namespace User\XmlRpc;

use Acl\Service\Acl as AclService;
use Application\Service\ApplicationTimeZone as TimeZoneService;
use User\Service\UserIdentity as UserIdentityService;
use XmlRpc\Handler\XmlRpcAbstractHandler;
use XmlRpc\Exception\XmlRpcActionDenied;

class UserHandler extends XmlRpcAbstractHandler
{
    /**
     * Request is denied (wrong time zone)
     */
    const REQUEST_DENIED_WRONG_TIME_ZONE = 'Time zone is wrong or not registered here';

    /**
     * Model instance
     *
     * @var \User\Model\UserXmlRpc
     */
    protected $model;

    /**
     * Get model
     *
     * @return \User\Model\UserXmlRpc
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->serviceLocator
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserXmlRpc');
        }

        return $this->model;
    }

    /**
     * Set user time zone
     *
     * @param string $timeZone
     * @param string $requestSignature
     * @throws \XmlRpc\Exception\XmlRpcActionDenied
     * @return array
     */
    public function setUserTimeZone($timeZone, $requestSignature)
    {
        // check request signature
        if (!$this->isRequestAuthorized([$timeZone], $requestSignature)) {
            throw new XmlRpcActionDenied(self::REQUEST_UNAUTHORIZED);
        }

        // check an user's permission
        if (!AclService::checkPermission('xmlrpc_set_user_timezone')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        // check received time zone
        if (false === ($timeZoneId =
                array_search($timeZone,TimeZoneService::getTimeZones()))) {

            throw new XmlRpcActionDenied(self::REQUEST_DENIED_WRONG_TIME_ZONE);
        }

        // update the user's time zone
        if (true === ($result = $this->getModel()->setUserTimeZone($this->
                userIdentity['user_id'], $this->userIdentity['nick_name'], $timeZoneId))) {

            return self::SUCCESSFULLY_RESPONSE;
        }

        return self::REQUEST_BROKEN;
    }

    /**
     * Get user's info
     *
     * @param integer $userId
     * @throws \XmlRpc\Exception\XmlRpcActionDenied
     * @return array
     */
    public function getUserInfo($userId)
    {
        // check user permissions
        if (!AclService::checkPermission('xmlrpc_view_user_info')) {
            throw new XmlRpcActionDenied(self::REQUEST_DENIED);
        }

        $viewerNickName = !UserIdentityService::isGuest()
            ? $this->userIdentity['nick_name']
            : null;

        // get user info
        if (false !== ($userInfo = $this->getModel()->
                getXmlRpcUserInfo($userId, $this->userIdentity['user_id'], $viewerNickName))) {

            return $userInfo;
        }

        return [];
    }
}