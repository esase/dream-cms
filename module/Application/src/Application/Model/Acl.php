<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;

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
     * Get all roles
     *
     * @return array
     */
    public function getAllRoles()
    {
        // generate cache name
        $cacheName = $this->staticCacheUtils->getCacheName(self::CACHE_ACL_ROLES);

        // check data in cache
        if (null === ($roles = $this->
                staticCacheUtils->getCacheInstance()->getItem($cacheName))) {

            $select = $this->select();
            $select->from('acl_roles')
                ->columns(array(
                    'id',
                    'name',
                    'system'
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // process localizations
            if ($resultSet) {
                foreach ($resultSet as $role) {
                    $roles[$role['id']] = array(
                        'name'   => $role['name'],
                        'system' => $role['system']
                    );
                }
            }

            // save data in cache
            $this->staticCacheUtils->getCacheInstance()->setItem($cacheName, $roles);
        }

        return $roles;
    }
}