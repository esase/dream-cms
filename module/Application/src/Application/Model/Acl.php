<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Exception;

class Acl extends Base
{
    /**
     * Default role admin id
     */
    const DEFAULT_ROLE_ADMIN  = 1;

    /**
     * Default role guest id
     */
    const DEFAULT_ROLE_GUEST  = 2;

    /**
     * Default user's id
     */
    const DEFAULT_USER_ID  = 1;

    /**
     * Default guest's id
     */
    const DEFAULT_GUEST_ID  = -1;

    /**
     * Default system's id
     */
    const DEFAULT_SYSTEM_ID  = 0;

    /**
     * Default role member
     */
    const DEFAULT_ROLE_MEMBER = 3;

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
     * @return array
     */
    public function getRolesList($excludeGuest = true)
    {
        $rolesList = array();

        $select = $this->select();
        $select->from('acl_roles')
            ->columns(array(
                'id',
                'name'
            ));

        if ($excludeGuest) {
            $select->where(array(
                new NotInPredicate('id', array(self::DEFAULT_ROLE_GUEST))
            ));
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
        $select->from('acl_roles')
            ->columns(array(
                'id',
                'name',
                'type'
            ))
            ->where(array(
                'id' => $id
            ));

        if ($excludeSystem) {
            $select->where(array(
                new NotInPredicate('type', array(self::ROLE_TYPE_SYSTEM))
            ));
        }

        if ($excludeAdministration) {
            $select->where(array(
                new NotInPredicate('id', array(self::DEFAULT_ROLE_ADMIN))
            ));
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
            $select->from('acl_resources_actions_track')
                ->columns(array(
                    'id'
                ))
                ->where(array(
                   'connection_id' => $resource['id']
                ));

            $userId != self::DEFAULT_GUEST_ID
                ? $select->where(array('user_id' => $userId))
                : $select->where->IsNull('user_id');

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            // add a new acl action track
            if (!$result->current()) {
                $values = array(
                    'connection_id' => $resource['id'],
                    'actions' => $resetActions ? $resetValue : 1,
                    'actions_last_reset' => new Expression('unix_timestamp()')
                );

                if ($userId != self::DEFAULT_GUEST_ID) {
                    $values = array_merge($values, array(
                        'user_id' => $userId,
                    ));
                }

                $insert = $this->insert()
                    ->into('acl_resources_actions_track')
                    ->values($values);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }
            else {
                // update the existing acl action track
                if ($resetActions) {
                    $update = $this->update()
                        ->table('acl_resources_actions_track')
                        ->set(array(
                            'actions' => $resetValue,
                            'actions_last_reset' => new Expression('unix_timestamp()')
                        ))
                        ->where(array(
                            'connection_id' => $resource['id']
                        ));

                    $userId != self::DEFAULT_GUEST_ID
                        ? $update->where(array('user_id' => $userId))
                        : $update->where->IsNull('user_id');

                    $update->where(array('actions_last_reset' => $resource['actions_last_reset']));

                    $statement = $this->prepareStatementForSqlObject($update);
                    $result = $statement->execute();

                    // action was reset before, just increase it
                    if (!$result->count()) {
                        // just increase the action
                        $update = $this->update()
                            ->table('acl_resources_actions_track')
                            ->set(array(
                                'actions' => new Expression('actions + 1')
                            ))
                            ->where(array(
                                'connection_id' => $resource['id']
                            ));
        
                        $userId != self::DEFAULT_GUEST_ID
                            ? $update->where(array('user_id' => $userId))
                            : $update->where->IsNull('user_id');
    
                        $statement = $this->prepareStatementForSqlObject($update);
                        $statement->execute();
                    }
                }
                else {
                    // just increase the action
                    $update = $this->update()
                        ->table('acl_resources_actions_track')
                        ->set(array(
                            'actions' => new Expression('actions + 1')
                        ))
                        ->where(array(
                            'connection_id' => $resource['id']
                        ));
    
                    $userId != self::DEFAULT_GUEST_ID
                        ? $update->where(array('user_id' => $userId))
                        : $update->where->IsNull('user_id');

                    $statement = $this->prepareStatementForSqlObject($update);
                    $statement->execute();
                }
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get acl resources
     *
     * @param integer $roleId
     * @param integer $userId
     * @return array
     */
    public function getAclResources($roleId, $userId)
    {
        $currentTime = time();

        $connectionSelect = $this->select();
        $connectionSelect->from(array('d' => 'acl_resources_connections_settings'))
            ->columns(array(
                'id'
            ))
            ->order('d.user_id desc')
            ->limit(1)
            ->where(array('d.connection_id' => new Expression('a.id')))
            ->where
                ->and->isNull('d.user_id')
            ->where
                ->or->equalTo('d.connection_id', new Expression('a.id'))
                ->and->equalTo('d.user_id', $userId);

        $extraTrackCondition = $userId == self::DEFAULT_GUEST_ID
            ? 'i.user_id is null'
            : 'i.user_id = ' . (int) $userId;

        $mainSelect = $this->select();
        $mainSelect->from(array('a' => 'acl_resources_connections'))
            ->columns(array(
                'id'
            ))
            ->join(
                array('b' => 'acl_resources'),
                'a.resource = b.id',
                array(
                    'resource'
                )
            )
            ->join(
                array('c' => 'acl_resources_connections_settings'),
                new Expression('c.id = (' .$this->getSqlStringForSqlObject($connectionSelect) . ')'),
                array(
                    'date_start',
                    'date_end',
                    'actions_limit',
                    'actions_reset'
                ),
                'left'
            )
            ->join(
                array('i' => 'acl_resources_actions_track'),
                new Expression('i.connection_id = c.connection_id and ' . $extraTrackCondition),
                array(
                    'actions',
                    'actions_last_reset',
                    'permission' => new Expression('if (
                        c.id is null
                            or
                        (c.date_start = 0 or (? >= c.date_start))    
                            and
                        (c.date_end = 0 or (? <= c.date_end))    
                            and
                        (c.actions_limit = 0 or i.actions is null or c.actions_limit > i.actions), "' .
                        self::ACTION_ALLOWED . '", "' .
                        self::ACTION_DISALLOWED . '")',array($currentTime, $currentTime))
                ),
                'left'
            )
            ->where(array('role' => $roleId));

        $statement = $this->prepareStatementForSqlObject($mainSelect);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}