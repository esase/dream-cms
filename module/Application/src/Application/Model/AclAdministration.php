<?php

namespace Application\Model;

use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Utility\Pagination as PaginationUtility;
use Application\Service\Service as ApplicationService;
use Zend\Db\Sql\Predicate\In as InPredicate;

class AclAdministration extends Acl
{
    /**
     * Delete roles
     *
     * @param array $roles
     * @return boolean|string
     */
    public function deleteRoles(array $roles)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('acl_roles')
                ->where(array(
                    'type' => self::ROLE_TYPE_CUSTOM,
                    new InPredicate('id', $roles)
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
     *      array type
     * @return object
     */
    public function getRoles($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'name',
            'type'
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

        // add filters
        if (!empty($filters['type']) && is_array($filters['type'])) {
            
            $select->where->in('type', $filters['type']);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}