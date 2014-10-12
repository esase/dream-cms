<?php
namespace Page\Model;

use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Localization\Service\Localization as LocalizationService;
use Page\Model\Page as PageModel;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;

class PageAdministration extends PageBase
{
    /**
     * Get dependent pages
     *
     * @param integer $pageId
     * @return object
     */
    public function getDependentPages($pageId)
    {
        $select = $this->select();
        $select->from(['a' => 'page_system_page_depend'])
            ->columns([
            ])
            ->join(
                ['b' => 'page_system'],
                'b.id = a.page_id',
                [
                    'title'
                ]
            )
            ->where([
                'a.depend_page_id' => $pageId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }

    /**
     * Get structure pages
     *
     * @param integer $parentId
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string status
     *      string redirect
     * @return object
     */
    public function getStructurePages($parentId = null, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'a.id',
            'position',
            'a.active',
            'redirect'
        ];

        $orderType = !$orderType || $orderType == 'asc'
            ? 'asc'
            : 'desc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'position';

        $select = $this->select();
        $select->from(['a' => 'page_structure'])
            ->columns([
                'id',
                'position' => 'left_key',
                'type',
                'title',
                'active',
                'redirect' => 'redirect_url',
                'left_key',
                'right_key',
                'system_page'
            ])
            ->join(
                ['b' => 'page_system'],
                'b.id = a.system_page',
                [
                    'system_title' => 'title'
                ],
                'left'
            )
            ->join(
                ['c' => 'page_system_page_depend'],
                'a.system_page = c.depend_page_id',
                [
                    'depend_page' => 'id'
                ],
                'left'
            )
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType)
            ->where([
                'a.language' => LocalizationService::getCurrentLocalization()['language']
            ]);

        null === $parentId
            ? $select->where->isNull('a.parent_id')
            : $select->where(['a.parent_id' => $parentId]);

        // filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active' :
                    $select->where([
                        'active' => PageModel::PAGE_STATUS_ACTIVE
                    ]);
                    break;
                default :
                    $select->where->IsNull('a.active');
            }
        }

        // filter by redirect
        if (!empty($filters['redirect'])) {
            switch ($filters['redirect']) {
                case 'redirected' :
                    $select->where->IsNotNull('a.redirect_url');
                    break;
                default :
                    $select->where->IsNull('a.redirect_url');
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}