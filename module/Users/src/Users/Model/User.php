<?php

namespace Users\Model;

use Zend\Db\ResultSet\ResultSet;

class User extends Base
{
    /**
     * Reset an user's password
     *
     * @param integer $userId
     * @return array|string
     */
    public function resetUserPassword($userId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();
 
            $newPassword = $this->generateRandString();
            $passwordSalt = $this->generateRandString();
 
            $update = $this->update()
                ->table('users')
                ->set(array(
                    'salt' => $passwordSalt,
                    'password' => $this->generatePassword($newPassword, $passwordSalt),
                    'activation_code' => ''
                ))
                ->where(array(
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return array(
            'password' => $newPassword
        );
    }

    /**
     * Generate a new activation code
     *
     * @param integer $userId
     * @return array|string
     */
    public function generateActivationCode($userId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();
            $activationCode = $this->generateRandString();

            $update = $this->update()
                ->table('users')
                ->set(array(
                    'activation_code' => $activationCode
                ))
                ->where(array(
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return array(
            'activation_code' => $activationCode
        );
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
        $select->from('users')
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