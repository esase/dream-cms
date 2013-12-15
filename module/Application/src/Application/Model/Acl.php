<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Exception;

class Acl extends Base
{
    /**
     * Default role admin
     */
    const DEFAULT_ROLE_ADMIN  = 1;

    /**
     * Default role guest
     */
    const DEFAULT_ROLE_GUEST  = 2;

    /**
     * Default guest id
     */
    const DEFAULT_GUEST_ID  = -1;

    /**
     * Default system id
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
     * @return boolean|string
     */
    public function increaseAclAction($userId, array $resource, $resetActions = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // add new acl action track
            if (!$resource['actions']) {
                $query = $this->insert()
                    ->into('acl_resources_actions_track')
                    ->values(array(
                        'connection_id' => $resource['id'],
                        'user_id' => $userId,
                        'actions' => 1,
                        'actions_last_reset' => new Expression('unix_timestamp()')
                    ));
            }
            else {
                // update existing acl action track
                $actionsCounter = $resetActions
                    ? 1
                    : new Expression('actions + 1');

                $updateFields = array();
                $updateFields['actions'] = $actionsCounter;

                if ($resetActions) {
                    $updateFields['actions_last_reset'] = new Expression('unix_timestamp()');
                }

                $query = $this->update()
                    ->table('acl_resources_actions_track')
                    ->set($updateFields)
                    ->where(array(
                        'connection_id' => $resource['id'],
                        'user_id' => $userId
                    ));
            }

            $statement = $this->prepareStatementForSqlObject($query);
            $statement->execute();

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
                new Expression('i.connection_id = c.connection_id and i.user_id = ?', array(
                    $userId
                )),
                array(
                    'actions',
                    'actions_last_reset',
                    'permission' => new Expression('if (c.id is null or
                        (c.date_start = 0 or (? >= c.date_start and ? <= c.date_end))
                            and
                        (c.actions_limit = 0 or i.actions is null or c.actions_limit > i.actions)
                            and
                        (c.date_start <> 0 or c.actions_limit <> 0), "' .
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