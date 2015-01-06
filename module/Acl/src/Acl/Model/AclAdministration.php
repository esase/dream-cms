<?php
namespace Acl\Model;

use Acl\Event\AclEvent;
use Application\Utility\ApplicationErrorLogger;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Exception;

class AclAdministration extends AclBase
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
        $select->from('acl_role')
            ->columns([
                'id'
            ])
            ->where(['name' => $roleName]);

        if ($roleId) {
            $select->where([
                new NotInPredicate('id', [$roleId])
            ]);
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
                ->table('acl_role')
                ->set($roleInfo)
                ->where([
                    'id' => $roleId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit acl role event
        AclEvent::fireEditAclRoleEvent($roleId);
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
                ->into('acl_role')
                ->values(array_merge($roleInfo, [
                    'type' => self::ROLE_TYPE_CUSTOM
                ]));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add acl role event
        AclEvent::fireAddAclRoleEvent($insertId);
        return $insertId;
    }

    /**
     * Edit resource's settings
     *
     * @param integer $connectionId
     * @param integer $resourceId
     * @param integer $roleId
     * @param array $settings
     *      integer actions_limit
     *      integer actions_reset
     *      integer date_start
     *      integer date_end
     * @param integer $userId
     * @param boolean $cleanActionCounter
     * @return integer|string
     */
    public function editResourceSettings($connectionId, $resourceId, $roleId, array $settings = [], $userId = 0, $cleanActionCounter = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // delete old settings
            $delete = $this->delete()
                ->from('acl_resource_connection_setting')
                ->where([
                    'connection_id' => $connectionId
                ]);

            if (!$userId) {
                $delete->where->IsNull('user_id'); // global settings
            }
            else {
                $delete->where([
                    'user_id' => $userId // local settings
                ]);
            }

            $statement = $this->prepareStatementForSqlObject($delete);
            $statement->execute();

            // check settings
            $processedSettings = [];
            foreach ($settings as $name => $value) {
                if ((int) $value) {
                    // skip empty values
                    $processedSettings[$name] = $value;
                }
            }

            if ($processedSettings) {
                $extraValues = $userId
                    ? ['user_id' => $userId, 'connection_id' => $connectionId]
                    : ['connection_id' => $connectionId];

                $insert = $this->insert()
                    ->into('acl_resource_connection_setting')
                    ->values(array_merge($processedSettings, $extraValues));
    
                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            // clean the action counter
            if ($cleanActionCounter && $userId) {
                $delete = $this->delete()
                    ->from('acl_resource_action_track')
                    ->where([
                        'connection_id' => $connectionId,
                        'user_id' => $userId
                    ]);

                $statement = $this->prepareStatementForSqlObject($delete);
                $statement->execute();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit acl resource settings event
        AclEvent::fireEditAclResourceSettingsEvent($connectionId, $resourceId, $roleId, $userId);
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
            $select->from('acl_resource_connection')
                ->columns([
                    'resource'
                ])
                ->where([
                    'role' => $roleId,
                    'resource' => $resourceId
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            // add a new connection
            if (!$result->current()) {
                $insert = $this->insert()
                    ->into('acl_resource_connection')
                    ->values([
                        'role' => $roleId,
                        'resource' => $resourceId
                    ]);

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the allow acl resource event
        AclEvent::fireAllowAclResourceEvent($resourceId, $roleId);
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
                ->from('acl_resource_connection')
                ->where([
                    'role' => $roleId,
                    'resource' => $resourceId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the disallow acl resource event
        AclEvent::fireDisallowAclResourceEvent($resourceId, $roleId);
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
                ->from('acl_role')
                ->where([
                    'type' => self::ROLE_TYPE_CUSTOM,
                    'id' => $roleId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the delete acl role event
        AclEvent::fireDeleteAclRoleEvent($roleId);
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
    public function getRoles($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('acl_role')
            ->columns([
                'id',
                'name',
                'type'
            ])
            ->order($orderBy . ' ' . $orderType);

        // filter by type
        if (!empty($filters['type'])) {
            $select->where([
                'type' => $filters['type']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

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
    public function getResources($roleId, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'connection'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'acl_resource'])
            ->columns([
                'id',
                'description'
            ])
            ->join(
                ['b' => 'application_module'],
                new Expression('a.module = b.id and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'module' => 'name'
                ]
            )
            ->join(
                ['c' => 'acl_resource_connection'],
                new Expression('a.id = c.resource and c.role = ?', [$roleId]),
                [
                    'connection' => 'id'
                ],
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
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

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
        $select->from(['a' => 'acl_resource_connection'])
            ->columns([
                'connection' => 'id',
                'role',
                'resource'
            ])
            ->join(
                ['b' => 'acl_role'],
                'b.id = a.role',
                [
                    'role_name' => 'name',
                    'role_type' => 'type'
                ]
            )
            ->join(
                ['c' => 'acl_resource'],
                'c.id = a.resource',
                [
                    'resource_description' => 'description'
                ]
            )
            ->join(
                ['cc' => 'application_module'],
                new Expression('c.module = cc.id and cc.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->join(
                ['d' => 'acl_resource_connection_setting'],
                new Expression('a.id = d.connection_id and ' . $extraCondition),
                [
                    'date_start',
                    'date_end',
                    'actions_limit',
                    'actions_reset'
                ],
                'left'
            )
            ->where([
                'a.id' => $connectionId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}