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
use Zend\Db\ResultSet\ResultSet;
use Exception;

class UserWidget extends UserBase
{
    /**
     * Reset an user's password
     *
     * @param array $userInfo
     * @return boolean|string
     */
    public function resetUserPassword(array $userInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();
 
            $newPassword = $this->generateRandString();
 
            $update = $this->update()
                ->table('user_list')
                ->set([
                    'password' => $this->generatePassword($newPassword),
                    'activation_code' => null,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'user_id' => $userInfo['user_id']
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userInfo['user_id']);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }
        
        // fire the user password reset event
        UserEvent::fireUserPasswordResetEvent($userInfo['user_id'], $userInfo, $newPassword);

        return true;
    }

    /**
     * Generate a new activation code
     *
     * @param array $userInfo
     * @return boolean|string
     */
    public function generateActivationCode(array $userInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();
            $activationCode = $this->generateRandString();

            $update = $this->update()
                ->table('user_list')
                ->set([
                    'activation_code' => $activationCode,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'user_id' => $userInfo['user_id']
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userInfo['user_id']);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the user password reset request event
        UserEvent::fireUserPasswordResetRequestEvent($userInfo['user_id'], $userInfo, $activationCode);

        return true;
    }

    /**
     * Check activation code
     *
     * @param integer $userId
     * @param string $activationCode
     * @return boolean
     */
    public function checkActivationCode($userId, $activationCode)
    {
        $select = $this->select();
        $select->from('user_list')
            ->columns([
                'user_id'
            ])
            ->where([
                'user_id' => $userId,
                'activation_code' => $activationCode
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }
}