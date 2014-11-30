<?php
namespace Acl\Model;

use Application\Model\ApplicationAbstractBase;
use Application\Utility\ApplicationErrorLogger;
use User\Model\UserBase as UserBaseModel;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Exception;

class AclBase extends ApplicationAbstractBase
{
    /**
     * Default role admin id
     */
    const DEFAULT_ROLE_ADMIN  = 1;

    /**
     * Default role admin name
     */
    const DEFAULT_ROLE_ADMIN_NAME  = 'admin';

    /**
     * Default role guest id
     */
    const DEFAULT_ROLE_GUEST  = 2;

    /**
     * Default role guest name
     */
    const DEFAULT_ROLE_GUEST_NAME  = 'guest';

    /**
     * Default role member
     */
    const DEFAULT_ROLE_MEMBER = 3;

    /**
     * Default role member name
     */
    const DEFAULT_ROLE_MEMBER_NAME  = 'member';

    /**
     * Allowed action
     */
    const ACTION_ALLOWED = 'allowed';

    /**
     * Disallowed action
     */
    const ACTION_DISALLOWED = 'disallowed';

    /**
     * Role type system
     */
    const ROLE_TYPE_SYSTEM = 'system';

    /**
     * Role type custom
     */
    const ROLE_TYPE_CUSTOM = 'custom';

    /**
     * Get list of all roles
     *
     * @param boolean $excludeGuest
     * @param boolean $excludeAdmin
     * @return array
     */
    public function getRolesList($excludeGuest = true, $excludeAdmin = false)
    {
        $rolesList = [];

        $select = $this->select();
        $select->from('acl_role')
            ->columns([
                'id',
                'name'
            ]);

        if ($excludeGuest) {
            $select->where([
                new NotInPredicate('id', [self::DEFAULT_ROLE_GUEST])
            ]);
        }

        if ($excludeAdmin) {
            $select->where([
                new NotInPredicate('id', [self::DEFAULT_ROLE_ADMIN])
            ]);
        }

        $select->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        foreach ($resultSet as $role) {
            $rolesList[$role->id] = $role->name;
        }

        return $rolesList;
    }

    /**
     * Get role info
     *
     * @param integer $id
     * @param boolean $excludeSystem
     * @param boolean $excludeAdministration
     * @return array
     */
    public function getRoleInfo($id, $excludeSystem = true, $excludeAdministration = false)
    {
        $select = $this->select();
        $select->from('acl_role')
            ->columns([
                'id',
                'name',
                'type'
            ])
            ->where([
                'id' => $id
            ]);

        if ($excludeSystem) {
            $select->where([
                new NotInPredicate('type', [self::ROLE_TYPE_SYSTEM])
            ]);
        }

        if ($excludeAdministration) {
            $select->where([
                new NotInPredicate('id', [self::DEFAULT_ROLE_ADMIN])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Increase acl action
     *
     * @param integer $userId
     * @param array  $resource
     *      integer id
     *      string resource
     *      string permission
     *      integer date_start
     *      integer date_end
     *      integer actions_limit
     *      integer actions_reset
     *      integer actions
     *      integer actions_last_reset
     * @param boolean $resetActions
     * @param integer $resetValue
     * @return boolean|string
     */
    public function increaseAclAction($userId, array $resource, $resetActions = false, $resetValue = 0)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // check the track existing
            $select = $this->select();
            $select->from('acl_resource_action_track')
                ->columns([
                    'id'
                ])
                ->where([
                   'connection_id' => $resource['id']
                ]);

            $userId != UserBaseModel::DEFAULT_GUEST_ID
                ? $select->where(['user_id' => $userId])
                : $select->where->IsNull('user_id');

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            // add a new acl action track
            if (!$result->current()) {
                $values = [
                    'connection_id' => $resource['id'],
                    'actions' => $resetActions ? $resetValue : 1,
                    'actions_last_reset' => time()
                ];

                if ($userId != UserBaseModel::DEFAULT_GUEST_ID) {
                    $values = array_merge($values, [
                        'user_id' => $userId,
                    ]);
                }

                $insert = $this->insert()
                    ->into('acl_resource_action_track')
                    ->values($values);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }
            else {
                // update the existing acl action track
                if ($resetActions) {
                    $update = $this->update()
                        ->table('acl_resource_action_track')
                        ->set([
                            'actions' => $resetValue,
                            'actions_last_reset' => time()
                        ])
                        ->where([
                            'connection_id' => $resource['id']
                        ]);

                    $userId != UserBaseModel::DEFAULT_GUEST_ID
                        ? $update->where(['user_id' => $userId])
                        : $update->where->IsNull('user_id');

                    $update->where(['actions_last_reset' => $resource['actions_last_reset']]);

                    $statement = $this->prepareStatementForSqlObject($update);
                    $result = $statement->execute();

                    // action was reset before, just increase it
                    if (!$result->count()) {
                        // just increase the action
                        $update = $this->update()
                            ->table('acl_resource_action_track')
                            ->set([
                                'actions' => new Expression('actions + 1')
                            ])
                            ->where([
                                'connection_id' => $resource['id']
                            ]);

                        $userId != UserBaseModel::DEFAULT_GUEST_ID
                            ? $update->where(['user_id' => $userId])
                            : $update->where->IsNull('user_id');

                        $statement = $this->prepareStatementForSqlObject($update);
                        $statement->execute();
                    }
                }
                else {
                    // just increase the action
                    $update = $this->update()
                        ->table('acl_resource_action_track')
                        ->set([
                            'actions' => new Expression('actions + 1')
                        ])
                        ->where([
                            'connection_id' => $resource['id']
                        ]);

                    $userId != UserBaseModel::DEFAULT_GUEST_ID
                        ? $update->where(['user_id' => $userId])
                        : $update->where->IsNull('user_id');

                    $statement = $this->prepareStatementForSqlObject($update);
                    $statement->execute();
                }
            }

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
     * Get allowed ACL resources
     *
     * @param integer $roleId
     * @param integer $userId
     * @return array
     */
    public function getAllowedAclResources($roleId, $userId)
    {
        $allowedResources = [];

        // process resources
        if (null != ($resources = $this->getAclResources($roleId, $userId))) {
            foreach ($resources as $resource) {
                if ($resource['permission'] == self::ACTION_DISALLOWED) {
                    // try to reset this resource
                    if (true !== ($result = $this->
                            resetAclResource($userId, $resource, self::ACTION_DISALLOWED, false, true))) {

                        continue;
                    }
                }

                $allowedResources[] = [
                    'description' => $resource['description']
                ];
            }
        }

        return $allowedResources;
    }

    /**
     * Check ACL resource's dates state
     *
     * @param array $resource
     *      integer id
     *      string resource
     *      string permission
     *      integer date_start
     *      integer date_end
     *      integer actions_limit
     *      integer actions_reset
     *      integer actions
     *      integer actions_last_reset
     * @return boolean
     */
    public function isAclResourceDatesActive($resource)
    {
        $currentTime = time();

        // a date start still not active
        if ($resource['date_start'] && $resource['date_start'] > $currentTime) {
            return false;
        }

        // a date end still not active
        if ($resource['date_end'] && $resource['date_end'] < $currentTime) {
            return false;
        }

        return true;
    }

    /**
     * Reset ACL resource
     *
     * @param integer $userId
     * @param array $resource
     *      integer id
     *      string resource
     *      string permission
     *      integer date_start
     *      integer date_end
     *      integer actions_limit
     *      integer actions_reset
     *      integer actions
     *      integer actions_last_reset
     * @param boolean $permissionResult
     * @param boolean $increaseActions
     * @param boolean $checkDates
     * return boolean
     */
    public function resetAclResource($userId, array $resource, $permissionResult, $increaseActions = true, $checkDates = false)
    {
        // check the resource's dates states (the dates should be empty or active)
        if ($checkDates && true !== ($result = $this->isAclResourceDatesActive($resource))) {
            return false;
        }

        // check the resources actions counter
        if ($resource['actions_limit']) {
            $reseted = false;

            // do we need reset all actions?
            if ($resource['actions_reset'] && time() >= $resource['actions_last_reset'] + $resource['actions_reset']) {
                // reset the resource's actions counter
                if (true !== ($result = $this->
                        increaseAclAction($userId, $resource, true, ($increaseActions ? 1 : 0)))) {

                    return false;
                }

                $reseted = true;
            }

            // common increase actions
            if ($increaseActions && !$reseted && $permissionResult === true) {
                // increase the resource's actions
                if (true !== ($result = $this->increaseAclAction($userId, $resource))) {
                    return false;
                }

                $reseted = true;
            }

            return $reseted;
        }

        return false;
    }

    /**
     * Get ACL resources
     *
     * @param integer $roleId
     * @param integer $userId
     * @return array
     */
    public function getAclResources($roleId, $userId)
    {
        $currentTime = time();

        $connectionSelect = $this->select();
        $connectionSelect->from(['d' => 'acl_resource_connection_setting'])
            ->columns([
                'id'
            ])
            ->limit(1)
            ->where(['d.connection_id' => new Expression('a.id')])
            ->where
                ->and->equalTo('d.user_id', $userId)
            ->where
                ->or->equalTo('d.connection_id', new Expression('a.id'))
                ->and->isNull('d.user_id');

        $extraTrackCondition = $userId == UserBaseModel::DEFAULT_GUEST_ID
            ? 'i.user_id IS NULL'
            : 'i.user_id = ' . (int) $userId;

        $mainSelect = $this->select();
        $mainSelect->from(['a' => 'acl_resource_connection'])
            ->columns([
                'id'
            ])
            ->join(
                ['b' => 'acl_resource'],
                'a.resource = b.id',
                [
                    'resource',
                    'description'
                ]
            )
            ->join(
                ['c' => 'acl_resource_connection_setting'],
                new Expression('c.id = (' .$this->getSqlStringForSqlObject($connectionSelect) . ')'),
                [
                    'date_start',
                    'date_end',
                    'actions_limit',
                    'actions_reset'
                ],
                'left'
            )
            ->join(
                ['i' => 'acl_resource_action_track'],
                new Expression('i.connection_id = c.connection_id and ' . $extraTrackCondition),
                [
                    'actions',
                    'actions_last_reset',
                    'permission' => new Expression('if (
                        c.id IS NULL
                            or
                        (c.date_start IS NULL or (? >= c.date_start))    
                            and
                        (c.date_end IS NULL or (? <= c.date_end))    
                            and
                        (c.actions_limit IS NULL or i.actions IS NULL or c.actions_limit > i.actions), "' .
                        self::ACTION_ALLOWED . '", "' .
                        self::ACTION_DISALLOWED . '")',[$currentTime, $currentTime])
                ],
                'left'
            )
            ->where(['role' => $roleId]);

        $statement = $this->prepareStatementForSqlObject($mainSelect);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}