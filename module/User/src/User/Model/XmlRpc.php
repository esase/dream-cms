<?php
namespace User\Model;

use Exception;
use Application\Utility\ErrorLogger;
use User\Event\Event as UserEvent;

class XmlRpc extends Base
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
        UserEvent::fireGetUserInfoViaXmlRpcEvent($userInfo->user_id, $userInfo->nick_name, $viewerId, $viewerNickName);
        return (array) $userInfo;
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
                ->set(array(
                    'time_zone' => $timeZoneId
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

        // fire set user's time zone via XmlRpc event
        UserEvent::fireSetTimezoneViaXmlRpcEvent($userId, $userName);
        return true;
    }
}