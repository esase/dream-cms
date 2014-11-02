<?php
namespace Page\Model;

use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Page\Model\PageNestedSet;
use Page\Event\PageEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;
use Exception;

class PageAdministration extends PageBase
{
    /**
     * Default pages module
     */
    const DEFAULT_PAGES_MODULE = 'Page';

    /**
     * Get basic page fields
     *
     * @param array $pageInfo
     * @return array
     */
    protected function getBasicPageFields(array $pageInfo)
    {
        $defaultValues = [
            'slug' => '',
            'user_menu_order' => 0,
            'footer_menu_order' => 0
        ];

        $basicFields = [
            'slug',
            'module',
            'layout',
            'title',
            'meta_description',
            'meta_keywords',
            'user_menu',
            'user_menu_order',
            'menu',
            'site_map',
            'footer_menu',
            'footer_menu_order',
            'active',
            'redirect_url',
            'system_page'
        ];

        $processedFields = [];
        foreach ($pageInfo as $name => $value) {
            if (!in_array($name, $basicFields)) {
                continue;
            }
            
            $processedFields[$name] = $value
                ? $value
                : (isset($defaultValues[$name]) ? $defaultValues[$name] : null);
        }

        return $processedFields;
    }

    /**
     * Edit page
     *
     * @param array $page
     * @param array $formData
     *      integer layout required
     *      string title optional|required for custom pages
     *      string slug optional|required for system pages
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
     *      string page_direction optional
     *      integer page optional
     *      array visibility_settings optional
     * @param boolean $isSystemPage
     * @param array $parent
     * @return boolean|string
     */
    public function editPage(array $page, array $formData, $isSystemPage, array $parent = [])
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // update page info
            $result = $this->getPageModel()->
                    updateNode($page['id'], $this->getBasicPageFields($formData), false);

            if (true !== $result) {
                $this->adapter->getDriver()->getConnection()->rollback();
                return $result;
            }

            // generate a new page slug automatically
            if (!$isSystemPage && empty($formData['slug'])) {
                $update = $this->update()
                    ->table('page_structure')
                    ->set([
                        'slug' => $this->generateSlug($page['id'], $formData['title'],
                                'page_structure', 'id', self::PAGE_SLUG_LENGTH, ['language' => $this->getCurrentLanguage()])
                    ])
                    ->where([
                        'id' => $page['id']
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();    
            }

            // move page 
            if ($parent) {
                $nearKey = !empty($formData['page'])
                    ? $formData['page']
                    : null;

                $pageDirection = !empty($formData['page_direction'])
                    ? $formData['page_direction']
                    : null;

                $result = $this->getPageModel()->movePage($page,
                        $parent, $this->getCurrentLanguage(), $nearKey, $pageDirection);

                if (true !== $result) {
                    $this->adapter->getDriver()->getConnection()->rollback();
                    return $result;
                }
            }

            // clear all old visibility settings
            $delete = $this->delete()
                ->from('page_visibility')
                ->where([
                    'page_id' => $page['id']
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // add new visibility settings
            if (!empty($formData['visibility_settings'])) {
                foreach ($formData['visibility_settings'] as $aclRoleId) {
                    $insert = $this->insert()
                        ->into('page_visibility')
                        ->values([
                            'page_id' => $page['id'],
                            'hidden' => $aclRoleId
                        ]);

                    $statement = $this->prepareStatementForSqlObject($insert);
                    $statement->execute();
                }
            }

            // move widgets
            if ($page['layout'] != $formData['layout']) {
                if (false !== ($layout = $this->getPageLayout($formData['layout']))) {
                    $update = $this->update()
                        ->table('page_widget_connection')
                        ->set([
                            'position_id' => $layout['default_position']
                        ])
                        ->where([
                            'page_id' => $page['id']
                        ]);
    
                    $statement = $this->prepareStatementForSqlObject($update);
                    $statement->execute();  
                }
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit page event
        PageEvent::fireEditPageEvent($page['id']);
        return true;
    }

    /**
     * Add page
     *
     * @param integer $parentLevel
     * @param integer $parentLeftKey
     * @param integer $parentRightKey
     * @param boolean $isSystemPage
     * @param array $pageInfo
     *      integer layout required
     *      string title optional|required for custom pages
     *      string slug optional|required for system pages
     *      integer module optional
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
     *      string page_direction optional
     *      integer page optional
     *      array widgets optional
     *      integer layout_default_position optional|required if widgets is not empty
     *      integer widget_default_layout optional
     *      array visibility_settings optional
     * @return integer|string
     */
    public function addPage($parentLevel, $parentLeftKey, $parentRightKey, $isSystemPage, array $pageInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $page = array_merge($this->getBasicPageFields($pageInfo), [
                'type' => $isSystemPage ? PageNestedSet::PAGE_TYPE_SYSTEM : PageNestedSet::PAGE_TYPE_CUSTOM,
                'language' => $this->getCurrentLanguage()
            ]);

            // get default pages module
            if (empty($page['module'])) {
                $page['module'] = $this->getModuleInfo(self::DEFAULT_PAGES_MODULE)['id'];
            }

            // add a page
            $nearKey = !empty($pageInfo['page'])
                ? $pageInfo['page']
                : null;

            $pageDirection = !empty($pageInfo['page_direction'])
                ? $pageInfo['page_direction']
                : null;

            $pageId = $this->getPageModel()->addPage($parentLevel,
                    $parentLeftKey, $parentRightKey, $page, $this->getCurrentLanguage(), $nearKey, $pageDirection);
 
            if (!is_numeric($pageId)) {
                $this->adapter->getDriver()->getConnection()->rollback();
                return $pageId;
            }

            // generate a new page slug automatically
            if (!$isSystemPage && empty($page['slug'])) {
                $update = $this->update()
                    ->table('page_structure')
                    ->set([
                        'slug' => $this->generateSlug($pageId, $page['title'],
                                'page_structure', 'id', self::PAGE_SLUG_LENGTH, ['language' => $this->getCurrentLanguage()])
                    ])
                    ->where([
                        'id' => $pageId
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();    
            }

            // add visibility settings
            if (!empty($pageInfo['visibility_settings'])) {
                foreach ($pageInfo['visibility_settings'] as $aclRoleId) {
                    $insert = $this->insert()
                        ->into('page_visibility')
                        ->values([
                            'page_id' => $pageId,
                            'hidden' => $aclRoleId
                        ]);

                    $statement = $this->prepareStatementForSqlObject($insert);
                    $statement->execute();
                }
            }

            // add dependent widgets
            if (!empty($pageInfo['widgets'])) {
                $widgetOrder = 1;
                foreach ($pageInfo['widgets'] as $widgetId) {
                    $insert = $this->insert()
                        ->into('page_widget_connection')
                        ->values([
                            'page_id' => $pageId,
                            'widget_id' => $widgetId,
                            'position_id' => $pageInfo['layout_default_position'],
                            'order' => $widgetOrder,
                            'layout' => $pageInfo['widget_default_layout']
                        ]);

                    $statement = $this->prepareStatementForSqlObject($insert);
                    $statement->execute();
                    $widgetOrder++;
                }                
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the add page event
        PageEvent::fireAddPageEvent($pageId);
        return $pageId;
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
            $this->clearWidgetsSettingsCache($pageInfo['id']);

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
     * @param integer $order
     * @return array
     */
    protected function getDependentSystemPages(array $pagesIds, array $dependentPagesFilter = [], $order = 0)
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
                'layout'
            ])
            ->join(
                ['b' => 'page_layout'],
                'a.layout = b.id',
                [
                    'layout_default_position' => 'default_position'
                ]
            )
            ->join(
                ['c' => 'page_widget_layout'],
                new Expression('c.default  = ?', [PageWidget::DEFAULT_WIDGET_LAYOUT]),                
                [
                    'widget_default_layout' => 'id'
                ]
            )
            ->join(
                ['d' => 'page_structure'],
                new Expression('a.slug = d.slug and d.language = ?', [$this->getCurrentLanguage()]),
                [],
                'left'
            )
            ->where->in('a.id', $pagesIds)
            ->where->isNull('d.id');

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
                'layout_default_position' =>  $page->layout_default_position,
                'widget_default_layout' => $page->widget_default_layout,
                'order' => $order,
                'system_page' => $page->id,
                'active' => PageNestedSet::PAGE_STATUS_ACTIVE
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
                $pages = $pages + $this->
                        getDependentSystemPages($dependentPagesIds, $dependentPagesFilter, $order + 1);
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

                return ($a['order'] > $b['order']) ? -1 : 1;
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
     * @return object
     */
    public function getStructurePages($parentId = null, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'position',
            'active',
            'widgets'
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
            ->join(
                ['c' => 'page_widget_connection'],
                'a.id = c.page_id',
                [
                    'widgets' => new Expression('count(c.id)')
                ],
                'left'
            )
            ->group('a.id')
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
                        'a.active' => PageNestedSet::PAGE_STATUS_ACTIVE
                    ]);
                    break;
                default :
                    $select->where->IsNull('a.active');
            }
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }
}