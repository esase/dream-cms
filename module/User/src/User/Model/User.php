<?php
namespace User\Model;

use Zend\Db\ResultSet\ResultSet;
use Exception;
use Application\Utility\ErrorLogger;
use User\Event\Event as UserEvent;

class User extends Base
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
            $passwordSalt = $this->generateRandString();
 
            $update = $this->update()
                ->table('user_list')
                ->set(array(
                    'salt' => $passwordSalt,
                    'password' => $this->generatePassword($newPassword, $passwordSalt),
                    'activation_code' => ''
                ))
                ->where(array(
                    'user_id' => $userInfo['user_id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userInfo['user_id']);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

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
                ->set(array(
                    'activation_code' => $activationCode
                ))
                ->where(array(
                    'user_id' => $userInfo['user_id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userInfo['user_id']);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the user password reset request event
        UserEvent::fireUserPasswordResetRequestEvent($userInfo['user_id'], $userInfo, $activationCode);
        return false;
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
            ->columns(array(
                'user_id'
            ))
            ->where(array(
                'user_id' => $userId,
                'activation_code' => $activationCode
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }
}