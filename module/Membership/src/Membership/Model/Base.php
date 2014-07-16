<?php
namespace Membership\Model;

use Application\Utility\ErrorLogger;
use Exception;
use Membership\Exception\MembershipException;
use Application\Model\AbstractBase;
use Application\Utility\FileSystem as FileSystemUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Payment\Model\Base as PaymentBaseModel;
use Application\Service\Service as ApplicationService;
use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;

class Base extends AbstractBase
{
    /**
     * Seconds in a day
     */
    const SECONDS_IN_DAY = 86400;

    /**
     * Membership level active status flag
     */
    const MEMBERSHIP_LEVEL_STATUS_ACTIVE = 1;

    /**
     * Membership level not active status flag
     */
    const MEMBERSHIP_LEVEL_STATUS_NOT_ACTIVE = 0;

    /**
     * Membership level connection active flag
     */
    const MEMBERSHIP_LEVEL_CONNECTION_ACTIVE = 1;

    /**
     * Membership level connection not active flag
     */
    const MEMBERSHIP_LEVEL_CONNECTION_NOT_ACTIVE = 0;

    /**
     * Membership level connection not notified
     */
    const MEMBERSHIP_LEVEL_CONNECTION_NOT_NOTIFIED = 0;

    /**
     * Membership level connection notified
     */
    const MEMBERSHIP_LEVEL_CONNECTION_NOTIFIED = 1;

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
     * Add a new membership connection
     *
     * @param integer $userId
     * @param integer $membershipId
     * @param integer $expire
     * @param integer $notify
     * @return integer|string
     */
    public function addMembershipConnection($userId, $membershipId, $expire, $notify)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('membership_level_connection')
                ->values(array(
                    'user_id' => $userId,
                    'membership_id' => $membershipId,
                    'expire_value' => $expire,
                    'notify_value' => $notify
                ));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $insertId;
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
                    'active' => self::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE,
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
        $select->from(array('a' => 'user_list'))
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
     * Delete the membership connection
     *
     * @param integer $connectionId
     * @return boolean|string
     */
    public function deleteMembershipConnection($connectionId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('membership_level_connection')
                ->where(array(
                    'id' => $connectionId
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
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
     * Get all user's membership connections
     *
     * @param integer $userId
     * @return object
     */
    public function getAllUserMembershipConnections($userId)
    {
        $select = $this->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'id',
            ))
            ->where(array(
                'user_id' => $userId
            ));

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
                'a.active' => self::MEMBERSHIP_LEVEL_CONNECTION_NOT_ACTIVE
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
     * @throws Membership\Exception\MembershipException
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
                    throw new MembershipException('Image deleting failed');
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
     * @param boolean $onlyActive
     * @return array
     */
    public function getRoleInfo($id, $onlyActive = false)
    {
        $select = $this->select();
        $select->from(array('a' => 'membership_level'))
            ->columns(array(
                'id',
                'title',
                'role_id',
                'cost',
                'lifetime',
                'expiration_notification',
                'description',
                'language',
                'image',
                'active'
            ))
            ->join(
                array('b' => 'membership_level_connection'),
                'b.membership_id = a.id',
                array(
                    'subscribers' => new Expression('count(b.id)'),
                ),
                'left'
            )
            ->where(array(
                'a.id' => $id
            ))
            ->group('a.id');

        if ($onlyActive) {
            $select->where(array(
                'a.active' => self::MEMBERSHIP_LEVEL_STATUS_ACTIVE
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get membership levels
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string title
     *      float cost
     *      integer lifetime
     *      integer role
     *      integer active
     * @return object
     */
    public function getMembershipLevels($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'title',
            'cost',
            'lifetime',
            'active',
            'subscribers'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'membership_level'))
            ->columns(array(
                'id',
                'title',
                'cost',
                'lifetime',
                'active',
                'description',
                'image',
                'role_id'
            ))
            ->join(
                array('b' => 'membership_level_connection'),
                'b.membership_id = a.id',
                array(
                    'subscribers' => new Expression('count(b.id)'),
                ),
                'left'
            )
            ->join(
                array('c' => 'application_acl_role'),
                'a.role_id = c.id',
                array(
                    'role' => 'name'
                )
            )
            ->join(
                array('d' => 'payment_currency'),
                new Expression('d.primary_currency = ?', array(PaymentBaseModel::PRIMARY_CURRENCY)),
                array(
                    'currency' => 'code'
                )
            )
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType)
            ->where(array(
                'a.language' => ApplicationService::getCurrentLocalization()['language']
            ));

        // filter by a title
        if (!empty($filters['title'])) {
            $select->where(array(
                'a.title' => $filters['title']
            ));
        }

        // filter by a cost
        if (!empty($filters['cost'])) {
            $select->where(array(
                'a.cost' => $filters['cost']
            ));
        }

        // filter by a lifetime
        if (!empty($filters['lifetime'])) {
            $select->where(array(
                'a.lifetime' => $filters['lifetime']
            ));
        }

        // filter by a role
        if (!empty($filters['role'])) {
            $select->where(array(
                'c.id' => $filters['role']
            ));
        }

        // filter by a active
        if (isset($filters['active']) && $filters['active'] != null) {
            $select->where(array(
                'a.active' => ((int) $filters['active'] == self::MEMBERSHIP_LEVEL_STATUS_ACTIVE 
                    ? $filters['active'] 
                    : self::MEMBERSHIP_LEVEL_STATUS_NOT_ACTIVE)
            ));
        }
        
        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}