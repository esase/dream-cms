<?php

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
            $result = $statement->execute();

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
     * @return object - ResultSet
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