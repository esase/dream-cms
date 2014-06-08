<?php

namespace Membership\Model;

use Application\Utility\ErrorLogger;
use Exception;
use Application\Model\AbstractBase;
use Application\Utility\FileSystem as FileSystemUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class Base extends AbstractBase
{
    /**
     * Seconds in a day
     */
    const SECONDS_IN_DAY = 86400;

    /**
     * Membership level active flag
     */
    const MEMBERSHIP_LEVEL_ACTIVE = 1;

    /**
     * Membership level not active flag
     */
    const MEMBERSHIP_LEVEL_NOT_ACTIVE = 0;

    /**
     * Membership level not notified
     */
    const MEMBERSHIP_LEVEL_NOT_NOTIFIED = 0;

    /**
     * Membership level notified
     */
    const MEMBERSHIP_LEVEL_NOTIFIED = 1;

    /**
     * Images directory
     * @var string
     */
    protected static $imagesDir = 'membership/';

    /**
     * Get images directory name
     *
     * @return string
     */
    public static function getImagesDir()
    {
        return self::$imagesDir;
    }

    /**
     * Activate the membership connection
     *
     * @param integer $connectionId
     * @return boolean
     */
    public function activateMembershipConnection($connectionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $time = time();
            $update = $this->update()
                ->table('membership_level_connection')
                ->set(array(
                    'active' => self::MEMBERSHIP_LEVEL_ACTIVE,
                    'expire_date' => new Expression('? + (expire_value * ?)', array($time, self::SECONDS_IN_DAY)),
                    'notify_date' => new Expression('? + (notify_value * ?)', array($time, self::SECONDS_IN_DAY))
                ))
                ->where(array(
                   'id' => $connectionId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $result = $statement->execute();
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Get users membership level 
     *
     * @return object
     */
    public function getUsersMembershipLevels()
    {
        $select = $this->select();
        $select->from(array('a' => 'user'))
            ->columns(array(
                'user_id',
                'language',
                'email',
                'nick_name',
            ))
            ->join(
                array('b' => 'membership_level_connection'),
                'a.user_id = b.user_id',
                array(
                    'connection_id' => 'id',
                    'active'
                )
            )
            ->join(
                array('c' => 'membership_level'),
                'b.membership_id = c.id',
                array(
                    'role_id',
                )
            )
            ->group('a.user_id')
            ->where->IsNull('a.role');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        return $resultSet->initialize($statement->execute());
    }

    /**
     * Get a user's membership connection from a queue
     *
     * @param integer $userId
     * @return array
     */
    public function getMembershipConnectionFromQueue($userId)
    {
        $select = $this->select();
        $select->from(array('a' => 'membership_level_connection'))
            ->columns(array(
                'id',
                'user_id'
            ))
            ->join(
                array('b' => 'membership_level'),
                'a.membership_id = b.id',
                array(
                    'role_id',
                    'lifetime',
                    'expiration_notification'
                )
            )
            ->where(array(
                'a.user_id' => $userId,
                'a.active' => self::MEMBERSHIP_LEVEL_NOT_ACTIVE
            ))
            ->order('a.id')
            ->limit(1);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        return $result->current();
    }

    /**
     * Delete the role
     *
     * @param array $roleInfo
     *      integer id required
     *      string image required
     * @return boolean|string
     */
    public function deleteRole($roleInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('membership_level')
                ->where(array(
                    'id' => $roleInfo['id']
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // delete the image
            if ($roleInfo['image']) {
                if (true !== ($imageDeleteResult = $this->deleteImage($roleInfo['image']))) {
                    throw new Exception('Image deleting failed');
                }
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Delete an membership's image
     *
     * @param string $imageName
     * @return boolean
     */
    protected function deleteImage($imageName)
    {
        return FileSystemUtility::deleteResourceFile($imageName, self::$imagesDir);
    }

    /**
     * Get all memberhip levels
     *
     * @param integer $roleId
     * @return object
     */
    public function getAllMembershipLevels($roleId)
    {
        $select = $this->select();
        $select->from('membership_level')
            ->columns(array(
                'id',
                'image'
            ))
            ->where(array(
                'role_id' => $roleId
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        return $resultSet->initialize($statement->execute());
    }

    /**
     * Get the role info
     *
     * @param integer $id
     * @return array
     */
    public function getRoleInfo($id)
    {
        $select = $this->select();
        $select->from('membership_level')
            ->columns(array(
                'id',
                'role_id',
                'cost',
                'lifetime',
                'expiration_notification',
                'description',
                'language',
                'image',
            ))
            ->where(array(
                'id' => $id
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}