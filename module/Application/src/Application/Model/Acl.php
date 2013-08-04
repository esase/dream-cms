<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

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
     * Increase acl action
     *
     * @param integer $connectionId
     * @param integer $userId
     * @param boolean $resetActions
     * @return boolean|string
     */
    public function increaseAclAction($connectionId, $userId, $resetActions = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $actionsCounter = $resetActions
                ? 1
                : new Expression('actions + 1');

            $updateFields = array();
            $updateFields['actions'] = $actionsCounter;

            if ($resetActions) {
                $updateFields['actions_last_reset'] = new Expression('unix_timestamp()');
            }

            $update = $this->update()
                ->table('acl_resources_users_connections')
                ->set($updateFields)
                ->where(array(
                    'connection_id' => $connectionId,
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (PDOException $e) {
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

        $select = $this->select();
        $select->from(array('a' => 'acl_resources_connections'))
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
                array('c' => 'acl_resources_users_connections'),
                new Expression('c.connection_id = a.id and c.user_id = ?', array(
                    $userId
                )),
                array(
                    'date_start',
                    'date_end',
                    'actions_limit',
                    'actions',
                    'actions_reset',
                    'actions_last_reset',
                    'permission' => new Expression('if (c.connection_id is null or
                        (c.date_start = 0 or (? >= c.date_start and ? <= c.date_end))
                            and
                        (c.actions_limit = 0 or (c.actions_limit > c.actions))
                            and
                        (c.date_start <> 0 or c.actions_limit <> 0), "' .
                        self::ACTION_ALLOWED . '", "' .
                        self::ACTION_DISALLOWED . '")',array($currentTime, $currentTime))
                ),
                'left'
            )
            ->where(array('role' => $roleId));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }
}