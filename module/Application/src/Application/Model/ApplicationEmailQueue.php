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
namespace Application\Model;

use Application\Utility\ApplicationErrorLogger;
use Zend\Db\ResultSet\ResultSet;
use Exception;

class ApplicationEmailQueue extends ApplicationBase
{
    /**
     * Delete message
     *
     * @param integer $messageId
     * @return boolean|string
     */
    public function deleteMessage($messageId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('application_email_queue')
                ->where([
                    'id' => $messageId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get messages
     *
     * @param integer $limit
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getMessages($limit)
    {
        $select = $this->select();
        $select->from('application_email_queue')
            ->columns([
                'id',
                'email',
                'title',
                'message'
            ])
            ->limit($limit)
            ->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }

    /**
     * Create message
     *
     * @param string $email
     * @param string $title
     * @param string $message
     * @return boolean|string
     */
    public function createMessage($email, $title, $message)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('application_email_queue')
                ->values([
                    'email' => $email,
                    'title' => $title,
                    'message' => $message,
                    'created' => time()
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }
}