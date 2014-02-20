<?php

namespace User\Model;

use Exception;
use Application\Utility\ErrorLogger;

class XmlRpc extends Base
{
    /**
     * Get user info
     *
     * @param integer $userId
     * @param string $field
     * @return array
     */
    public function getUserInfo($userId, $field = null)
    {
        if (null != ($userInfo = parent::getUserInfo($userId, $field))) {
            // remove all private fields
            foreach ($this->privateFields as $privateField) {
                if (isset($userInfo[$privateField])) {
                    unset($userInfo[$privateField]);
                }
            }
        }

        return $userInfo;
    }

    /**
     * Set user time zone
     *
     * @param integer $userId
     * @param string $timeZone
     * @return boolean|string
     */
    public function setUserTimeZone($userId, $timeZone)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user')
                ->set(array(
                    'time_zone' => $timeZone
                ))
                ->where(array('user_id' => $userId));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
            
            // remove user cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }
}