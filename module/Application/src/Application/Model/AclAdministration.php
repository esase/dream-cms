<?php

namespace Application\Model;

use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Utility\Pagination as PaginationUtility;
use Application\Service\Service as ApplicationService;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\ResultSet\ResultSet;
use Exception;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;

class AclAdministration extends Acl
{
    /**
     * Is a role's name free
     *
     * @param string $roleName
     * @param integer $roleId
     * @return boolean
     */
    public function isRoleNameFree($roleName, $roleId = 0)
    {
        $select = $this->select();
        $select->from('acl_roles')
            ->columns(array(
                'id'
            ))
            ->where(array('name' => $roleName));

        if ($roleId) {
            $select->where(array(
                new NotInPredicate('id', array($roleId))
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Edit role
     *
     * @param integer $roleId
     * @param array $roleInfo
     *      string name
     * @return boolean|string
     */
    public function editRole($roleId, array $roleInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('acl_roles')
                ->set($roleInfo)
                ->where(array(
                    'id' => $roleId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
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
     * Add a new role
     *
     * @param array $roleInfo
     *      string name
     * @return integer|string
     */
    public function addRole(array $roleInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('acl_roles')
                ->values(array_merge($roleInfo, array(
                    'type' => self::ROLE_TYPE_CUSTOM
                )));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return $insertId;
    }

    /**
     * Edit resource's settings
     *
     * @param integer $connectionId
     * @param array $settings
     *      integer actions_limit
     *      integer actions_reset
     *      integer date_start
     *      integer date_end
     * @param integer $userId
     * @param boolean $cleanActionCounter
     * @return integer|string
     */
    public function editResourceSettings($connectionId, array $settings = array(), $userId = 0, $cleanActionCounter = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // delete old settings
            $delete = $this->delete()
                ->from('acl_resources_connections_settings')
                ->where(array(
                    'connection_id' => $connectionId
                ));

            if (!$userId) {
                $delete->where->IsNull('user_id'); // global settings
            }
            else {
                $delete->where(array(
                    'user_id' => $userId // local settings
                ));
            }

            $statement = $this->prepareStatementForSqlObject($delete);
            $statement->execute();

            // check settings
            $saveSettings = false;
            foreach ($settings as $value) {
                if ((int) $value) {
                    $saveSettings = true;
                    break;
                }
            }

            if ($saveSettings) {
                $extraValues = $userId
                    ? array('user_id' => $userId, 'connection_id' => $connectionId)
                    : array('connection_id' => $connectionId);
    
                $insert = $this->insert()
                    ->into('acl_resources_connections_settings')
                    ->values(array_merge($settings, $extraValues));
    
                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            // clean the action counter
            if ($cleanActionCounter && $userId) {
                $delete = $this->delete()
                    ->from('acl_resources_actions_track')
                    ->where(array(
                        'connection_id' => $connectionId,
                        'user_id' => $userId
                    ));

                $statement = $this->prepareStatementForSqlObject($delete);
                $statement->execute();
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
     * Allow resource
     *
     * @param integer $roleId
     * @param integer $resourceId
     * @return boolean|string
     */
    public function allowResource($roleId, $resourceId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // check the existing connection
            $select = $this->select();
            $select->from('acl_resources_connections')
                ->columns(array(
                    'resource'
                ))
                ->where(array(
                    'role' => $roleId,
                    'resource' => $resourceId
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            // add a new connection
            if (!$result->current()) {
                $insert = $this->insert()
                    ->into('acl_resources_connections')
                    ->values(array(
                        'role' => $roleId,
                        'resource' => $resourceId
                    ));

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
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
     * Disallow resource
     *
     * @param integer $roleId
     * @param integer $resourceId
     * @return boolean|string
     */
    public function disallowResource($roleId, $resourceId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('acl_resources_connections')
                ->where(array(
                    'role' => $roleId,
                    'resource' => $resourceId
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Delete role
     *
     * @param integer $roleId
     * @return boolean|string
     */
    public function deleteRole($roleId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('acl_roles')
                ->where(array(
                    'type' => self::ROLE_TYPE_CUSTOM,
                    'id' => $roleId
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Get roles
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string type
     * @return object
     */
    public function getRoles($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('acl_roles')
            ->columns(array(
                'id',
                'name',
                'type'
            ))
            ->order($orderBy . ' ' . $orderType);

        // filter by type
        if (!empty($filters['type'])) {
            $select->where(array(
                'type' => $filters['type']
            ));
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get resources
     *
     * @param integer $roleId
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      array modules
     *      string status
     * @return object
     */
    public function getResources($roleId, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'acl_resources'))
            ->columns(array(
                'id',
                'description'
            ))
            ->join(
                array('b' => 'modules'),
                new Expression('a.module = b.id and b.active = ' . (int) self::MODULE_ACTIVE),
                array(
                    'module' => 'name'
                )
            )
            ->join(
                array('c' => 'acl_resources_connections'),
                new Expression('a.id = c.resource and c.role = ' . (int) $roleId),
                array(
                    'connection' => 'id'
                ),
                'left'
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by modules
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $select->where->in('module', $filters['modules']);
        }

        // filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'disallowed' :
                    $select->where->IsNull('c.id');
                    break;
                case 'allowed' :
                default :
                    $select->where->IsNotNull('c.id');
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get resource settings info
     *
     * @param integer $connectionId
     * @param integer $userId
     * @return array
     */
    public function getResourceSettings($connectionId, $userId = 0)
    {
        $extraCondition = $userId
            ? 'd.user_id = ' . (int) $userId
            : 'd.user_id is null';

        $select = $this->select();
        $select->from(array('a' => 'acl_resources_connections'))
            ->columns(array(
                'connection' => 'id',
                'role',
                'resource'
            ))
            ->join(
                array('b' => 'acl_roles'),
                'b.id = a.role',
                array(
                    'role_name' => 'name'
                )
            )
            ->join(
                array('c' => 'acl_resources'),
                'c.id = a.resource',
                array(
                    'resource_description' => 'description'
                )
            )
            ->join(
                array('d' => 'acl_resources_connections_settings'),
                new Expression('a.id = d.connection_id and ' . $extraCondition),
                array(
                    'date_start',
                    'date_end',
                    'actions_limit',
                    'actions_reset'
                ),
                'left'
            )
            ->where(array(
                'a.id' => $connectionId
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}