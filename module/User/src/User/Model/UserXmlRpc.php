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
namespace User\Model;

use Application\Utility\ApplicationErrorLogger;
use User\Event\UserEvent;
use Exception;

class UserXmlRpc extends UserBase
{
    /**
     * Get user info
     *
     * @param integer $userId
     * @param integer $viewerId
     * @param string $viewerNickName
     * @return array
     */
    public function getXmlRpcUserInfo($userId, $viewerId, $viewerNickName)
    {
        if (null != ($userInfo = parent::getUserInfo($userId))) {
            // remove all private fields
            foreach ($this->privateFields as $privateField) {
                if (isset($userInfo[$privateField])) {
                    unset($userInfo[$privateField]);
                }
            }
        }

        // fire the get user info via XmlRpc event
        UserEvent::fireGetUserInfoViaXmlRpcEvent($userInfo['user_id'], $userInfo['nick_name'], $viewerId, $viewerNickName);

        return $userInfo;
    }

    /**
     * Set user time zone
     *
     * @param integer $userId
     * @param string $userName
     * @param integer $timeZoneId
     * @return boolean|string
     */
    public function setUserTimeZone($userId, $userName, $timeZoneId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user_list')
                ->set([
                    'time_zone' => $timeZoneId,
                    'date_edited' => date('Y-m-d')
                ])
                ->where(['user_id' => $userId]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
            
            // remove user cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire set user's time zone via XmlRpc event
        UserEvent::fireSetTimezoneViaXmlRpcEvent($userId, $userName);

        return true;
    }
}