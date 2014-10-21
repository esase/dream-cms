<?php
namespace Page\Model;

use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Page\Model\Page as PageModel;
use Page\Utility\PageCache as PageCacheUtility;
use Page\Event\PageEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;

class PageAdministration extends PageBase
{
    /**
     * Page model instance
     * @var object  
     */
    protected $pageModel;

    /**
     * Get page model
     */
    protected function getPageModel()
    {
        if (!$this->pageModel) {
            $this->pageModel = $this->serviceLocator->get('Page\Model\Page');
        }

        return $this->pageModel;
    }

    /**
     * Add page
     *
     * @param integer $parentLevel
     * @param integer $parentRightKey
     * @param boolean $isSystemPage
     * @param string $type
     * @param array $page
     *      string slug required
     *      integer module required
     *      integer layout required
     *      string title optional
     *      string meta_description optional
     *      string meta_keywords optional
     *      integer user_menu optional
     *      integer user_menu_order optional
     *      integer menu optional
     *      integer site_map optional
     *      integer footer_menu optional
     *      integer footer_menu_order optional
     *      integer active optional
     *      string redirect_url optional
     *      integer system_page optional
     * @return boolean|string
     */
    public function addPage($parentLevel, $parentRightKey, $parentId, $isSystemPage, $pageInfo)
    {
        $page = [
            'slug' => $pageInfo['slug'],
            'module' => $pageInfo['module'],
            'layout' => $pageInfo['layout'],
            'parent_id' => $parentId,
            'title' => !empty($pageInfo['title']) ? $pageInfo['title'] : null,
            'meta_description' => !empty($pageInfo['meta_description']) ? $pageInfo['meta_description'] : null,
            'meta_keywords' => !empty($pageInfo['meta_keywords']) ? $pageInfo['meta_keywords'] : null,
            'user_menu' => !empty($pageInfo['user_menu']) ? $pageInfo['user_menu'] : null,
            'user_menu_order' => !empty($pageInfo['user_menu_order']) ? $pageInfo['user_menu_order'] : 0,
            'menu' => !empty($pageInfo['menu']) ? $pageInfo['menu'] : null,
            'site_map' => !empty($pageInfo['site_map']) ? $pageInfo['site_map'] : null,
            'footer_menu' => !empty($pageInfo['footer_menu']) ? $pageInfo['footer_menu'] : null,
            'footer_menu_order' => !empty($pageInfo['footer_menu_order']) ? $pageInfo['footer_menu_order'] : 0,
            'active' => !empty($pageInfo['active']) ? $pageInfo['active'] : null,
            'type' => $isSystemPage ? PageModel::PAGE_TYPE_SYSTEM : PageModel::PAGE_TYPE_CUSTOM,
            'language' => $this->getCurrentLanguage(),
            'redirect_url' => !empty($pageInfo['redirect_url']) ? $pageInfo['redirect_url'] : null,
            'system_page' => !empty($pageInfo['system_page']) ? $pageInfo['system_page'] : null
        ];

        $result = $this->getPageModel()->
            insertNode($parentLevel, $parentRightKey, $page, ['language' => $this->getCurrentLanguage()]);

        // add widgets
        if (is_numeric($result)) {
            // TODO: add widgets
            // TODO: add system notification
        }

        return $result;
    }

    /**
     * Delete page
     *
     * @param integer $pageInfo
     *      integer id
     *      string slug
     *      string type
     *      integer parent_id
     *      integer left_key
     *      integer right_key
     *      integer dependent_page
     *      string language
     * @return boolean|string
     */
    public function deletePage($pageInfo)
    {
        $result = $this->getPageModel()->
                deleteNode($pageInfo['left_key'], $pageInfo['right_key'], ['language' => $pageInfo['language']]);

        if (true === $result) {
            // clear cache
            PageCacheUtility::clearLanguageSensitivePageCaches($pageInfo['language'], $pageInfo['id']);

            // fire the delete page event
            PageEvent::fireDeletePageEvent($pageInfo['id']);
        }

        return $result;
    }

    /**
     * Get system pages map
     *
     * @param array $pagesIds
     * @param array $dependentPagesFilter
     * @return array
     */
    protected function getDependentSystemPages(array $pagesIds, array $dependentPagesFilter = [])
    {
        // we need to get recursively all selected pages and their dependent pages
        $pages = [];

        // get selected system pages
        $select = $this->select();
        $select->from(['a' => 'page_system'])
            ->columns([
                'id',
                'slug',
                'module',
                'user_menu',
                'user_menu_order',
                'menu',
                'site_map',
                'footer_menu',
                'footer_menu_order',
                'layout',                
                'order'
            ])
            ->join(
                ['b' => 'page_structure'],
                new Expression('a.slug = b.slug and b.language = ?', [$this->getCurrentLanguage()]),
                [],
                'left'
            )
            ->where->in('a.id', $pagesIds)
            ->where->isNull('b.id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        foreach ($resultSet as $page) {
            $dependentPagesFilter[] = $page->id;
            $pages[$page->id] = [
                'slug' =>  $page->slug,
                'module' =>  $page->module,
                'user_menu' =>  $page->user_menu,
                'user_menu_order' =>  $page->user_menu_order,
                'menu' =>  $page->menu,
                'site_map' =>  $page->site_map,
                'footer_menu' =>  $page->footer_menu,
                'footer_menu_order' =>  $page->footer_menu_order,
                'layout' =>  $page->layout,
                'order' => $page->order,
                'system_page' => $page->id,
                'active' => PageModel::PAGE_STATUS_ACTIVE
            ];
        }

        // check dependent pages
        if ($pages) {
            $select = $this->select();
            $select->from(['a' => 'page_system_page_depend'])
                ->columns([])
                ->join(
                    ['b' => 'page_system'],
                    'a.depend_page_id = b.id',
                    [
                        'id'
                    ]
                )
                ->join(
                    ['c' => 'page_structure'],
                    new Expression('b.slug = c.slug and c.language = ?', [$this->getCurrentLanguage()]),
                    [],
                    'left'
                ) 
                ->group('b.id')
                ->where->in('a.page_id', array_keys($pages))
                ->where->isNull('c.id');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $dependentPagesIds = [];
            foreach ($resultSet as $page) {
                if (in_array($page->id, $dependentPagesFilter)) {
                    continue;                    
                }

                $dependentPagesIds[] = $page->id;
            }

            // get dependent pages
            if ($dependentPagesIds) {
                $pages = $pages + $this->getDependentSystemPages($dependentPagesIds, $dependentPagesFilter);
            }
        }

        return $pages;
    }

    /**
     * Get system pages widgets
     *
     * @param array $pagesIds
     * @return array
     */
    protected function getDependentSystemPagesWidgets($pagesIds)
    {
        $widgets = [];
        $definedWidgetsIds = [];

        // get dependent widgets
        $select = $this->select();
        $select->from('page_system_widget_depend')
            ->columns([
                'page_id',
                'widget_id'
            ])
            ->order('order')
            ->where->in('page_id', $pagesIds);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        foreach ($resultSet as $widget) {
            // collect all widgest ids
            if (!in_array($widget->widget_id, $definedWidgetsIds)) {
                $definedWidgetsIds[] = $widget->widget_id;
            }

            $widgets[] = [
                'page_id' => $widget->page_id,
                'widget_id' => $widget->widget_id
            ];
        }

        $widgetsFilter = $definedWidgetsIds;

        // get all dependent widgets
        if ($widgetsFilter) {
            while (true) {
                $select = $this->select();
                $select->from('page_widget_depend')
                    ->columns([
                        'widget_id',
                        'depend_widget_id'
                    ])
                    ->where->in('widget_id', $widgetsFilter);

                $statement = $this->prepareStatementForSqlObject($select);
                $resultSet = new ResultSet;
                $resultSet->initialize($statement->execute());

                $widgetsFilter = [];                
                foreach ($resultSet as $widget) {                    
                    if (!in_array($widget['depend_widget_id'], $definedWidgetsIds)) {
                        $widgetsFilter[] = $definedWidgetsIds[] = $widget['depend_widget_id'];                        
                    }

                    // search dependent widgets
                    foreach ($widgets as $widgetInfo) {
                        if ($widgetInfo['widget_id'] == $widget['widget_id']) {
                            $widgets[] = [
                                'page_id' => $widgetInfo['page_id'],
                                'widget_id' => $widget['depend_widget_id']                            
                            ];
                        }
                    }
                }

                if (!$widgetsFilter) {
                    break;
                }
            }
        }

        return $widgets;
    }

    /**
     * Get system pages map
     *
     * @param array $pagesIds
     * @return array
     */
    public function getSystemPagesMap(array $pagesIds)
    {
        if (null != ($systemPagesMap = $this->getDependentSystemPages($pagesIds))) {
            // sort pages map
            uasort($systemPagesMap, function($a, $b) {
                if ($a['order'] == $b['order']) {
                    return 0;
                }

                return ($a['order'] < $b['order']) ? -1 : 1;
            });

            // get list of widgets
            if (null != ($systemPagesWidgets =
                    $this->getDependentSystemPagesWidgets(array_keys($systemPagesMap)))) {

                // process received widgets
                foreach ($systemPagesWidgets as $widget) {
                    $systemPagesMap[$widget['page_id']]['widgets'][] = $widget['widget_id'];
                }
            }
        }

        return $systemPagesMap;
    }

    /**
     * Get dependent pages
     *
     * @param integer $pageId
     * @return object
     */
    public function getDependentPages($pageId, $checkInStructure = true)
    {
        $select = $this->select();
        $select->from(['a' => 'page_system_page_depend'])
            ->columns([
            ]);

        if ($checkInStructure) {
            $select->join(
                ['b' => 'page_structure'],
                new Expression('a.page_id = b.system_page and b.language = ?', [$this->getCurrentLanguage()]),
                []
            )
            ->join(
                ['c' => 'page_system'],
                'b.system_page = c.id',
                [
                    'title'
                ]
            );
        }
        else {            
            $select->join(
                ['b' => 'page_system'],
                'a.page_id = b.id',
                [
                    'title'
                ]
            );
        }

        $select->where([
            'a.depend_page_id' => $pageId
        ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }

    /**
     * Get system pages
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *  array modules
     * @return object
     */
    public function getSystemPages($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id'
        ];

        $orderType = !$orderType || $orderType == 'asc'
            ? 'asc'
            : 'desc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(['a' => 'page_system'])
            ->columns([
                'id',
                'title'
            ])
            ->join(
                ['b' => 'application_module'],
                'b.id = a.module',
                [
                    'module_name' => 'name'
                ]
            )
            ->join(
                ['c' => 'page_structure'],
                new Expression('a.slug = c.slug and c.language = ?', [$this->getCurrentLanguage()]),
                [],
                'left'
            )
            ->join(
                ['d' => 'page_system_page_depend'],
                'a.id = d.depend_page_id',
                [
                    'dependent_page' => 'id'
                ],
                'left'
            )
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType)
            ->where->isNull('c.id');

        // filter by modules
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $select->where->in('a.module', $filters['modules']);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
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
            'id',
            'position',
            'active',
            'redirect'
        ];

        $orderType = !$orderType || $orderType == 'asc'
            ? 'asc'
            : 'desc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'position';

        $dependentCheckSelect = $this->select();
        $dependentCheckSelect->from(['i' => 'page_system_page_depend'])
            ->columns([
                'id'
            ])
            ->join(
                ['f' => 'page_structure'],
                new Expression('i.page_id = f.system_page and f.language = ?', [$this->getCurrentLanguage()]),
                []
            )
            ->where(['a.system_page' => new Expression('i.depend_page_id')])
            ->limit(1);

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
                'system_page',
                'dependent_page' => new Expression('(' . $this->getSqlStringForSqlObject($dependentCheckSelect) . ')')
            ])
            ->join(
                ['b' => 'page_system'],
                'b.id = a.system_page',
                [
                    'system_title' => 'title'
                ],
                'left'
            )
            ->order($orderBy . ' ' . $orderType)
            ->where([
                'a.language' => $this->getCurrentLanguage()
            ]);

        null === $parentId
            ? $select->where->isNull('a.parent_id')
            : $select->where(['a.parent_id' => $parentId]);

        // filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active' :
                    $select->where([
                        'a.active' => PageModel::PAGE_STATUS_ACTIVE
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