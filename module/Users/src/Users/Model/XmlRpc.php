<?php

namespace Users\Model;

class XmlRpc extends Base
{
    /**
     * Get user info by Id
     *
     * @param integer $userId
     * @param boolean $isApiKey
     * @return array
     */
    public function getUserInfoById($userId, $isApiKey = false)
    {
        if (null != ($userInfo = parent::getUserInfoById($userId, $isApiKey))) {
            // remove all private fields
            foreach ($this->privateFields as $privateField) {
                if (!empty($userInfo[$privateField])) {
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
                ->table('users')
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
        catch (\Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return true;
    }
}