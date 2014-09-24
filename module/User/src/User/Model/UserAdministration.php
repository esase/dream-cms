<?php
namespace User\Model;

use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Predicate\Like as LikePredicate;

class UserAdministration extends UserBase
{
    /**
     * Get users
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string nickname
     *      string email
     *      string status
     *      integer role
     * @return object
     */
    public function getUsers($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'nickname',
            'email',
            'registered',
            'status'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'user_list'])
            ->columns([
                'id' => 'user_id',
                'nickname' => 'nick_name',
                'email',
                'status',
                'registered',
                'role_id' => 'role'
            ])
            ->join(
                ['b' => 'acl_role'],
                'a.role = b.id',
                [
                    'role' => 'name'
                ]
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by nickname
        if (!empty($filters['nickname'])) {
            $select->where([
                new LikePredicate('nick_name', $filters['nickname'] . '%')
            ]);
        }

        // filter by email
        if (!empty($filters['email'])) {
            $select->where([
                'email' => $filters['email']
            ]);
        }

        // filter by status
        if (!empty($filters['status'])) {
            $select->where([
                'status' => $filters['status']
            ]);
        }

        // filter by role
        if (!empty($filters['role'])) {
            $select->where([
                'role' => $filters['role']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}