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
     * Get acl resources
     *
     * @param integer $roleId
     * @param integer $userId
     * @return array
     */
    public function getAclResources($roleId, $userId)
    {
        $select = $this->select();
        $select->from(array('a' => 'acl_resources_connections'))
            ->columns(array(
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
                    'action' => new Expression('if(c.connection_id is null or (c.actions_limit > 0 and
                            c.actions_limit > c.actions) or ? < c.date_expired,
                            "allowed", "not_allowed")', time())
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