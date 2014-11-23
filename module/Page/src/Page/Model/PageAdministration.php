<?php
namespace Page\Model;

use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Page\Model\PageNestedSet;
use Page\Event\PageEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate;
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
     * Get manage layout path
     *
     * @return string
     */
    public function getManageLayoutPath()
    {
        return $this->getPageModel()->getManageLayoutPath();   
    }

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
            'xml_map',
            'xml_map_update',
            'xml_map_priority',
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

        return array_merge($processedFields, [
            'date_edited' => date('Y-m-d')
        ]);
    }

    /**
     * Get max public widget order
     *
     * @param integer $pageId
     * @param integer $positionId
     * @return integer
     */
    protected function getMaxPublicWidgetOrder($pageId, $positionId)
    {
        // get a widget max order
        $select = $this->select();
        $select->from(['a' => 'page_widget_connection'])
            ->columns([
                'max_order' => new Expression('max(' .
                        $this->adapter->platform->quoteIdentifier('order') . ')')
            ])
            ->join(
                ['b' => 'page_widget'],
                new Expression('b.id = a.widget_id and b.type = ?', [self::WIDGET_TYPE_PUBLIC]),
                []
            )
            ->where([
                'a.page_id' => $pageId,
                'a.position_id' => $positionId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current() ? $result->current()['max_order'] + 1 : 1;
    }

    /**
     * Change public widgets position
     *
     * @param integer $pageId
     * @param integer $newlayoutId
     * @param integer $defaultPosition
     * @return void
     */
    protected function changePublicWidgetsPosition($pageId, $newlayoutId, $defaultPosition)
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget_connection'])
            ->columns([
                'id'
            ])
            ->join(
                ['b' => 'page_widget'],
                new Expression('b.id = a.widget_id and b.type = ?', [self::WIDGET_TYPE_PUBLIC]),                        
                []
            )
            ->join(
                ['c' => 'page_widget_position_connection'],
                new Expression('a.position_id = c.position_id and c.layout_id = ?', [$newlayoutId]),
                [],
                'left'
            )
            ->order('order')
            ->where([
                'a.page_id' => $pageId
            ])
            ->where->isNull('c.id');

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        if (count($result)) {
            $maxOrder = $this->getMaxPublicWidgetOrder($pageId, $defaultPosition);

            foreach ($result as $widgetConnection) {
                $update = $this->update()
                    ->table('page_widget_connection')
                    ->set([
                        'order' => $maxOrder,
                        'position_id' => $defaultPosition
                    ])
                    ->where([
                        'id' => $widgetConnection['id']
                    ]);

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
                $maxOrder++;
            }
        }
    }

    /**
     * Change page layout
     *
     * @param integer $newLayoutId
     * @param integer $pageId
     * @param integer $defaultPosition
     * @param integer $oldLayoutId
     * @return boolean|string
     */
    public function changePageLayout($newLayoutId, $pageId, $defaultPosition, $oldLayoutId)
    {
        if ($newLayoutId == $oldLayoutId) {
            return true;
        }

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('page_structure')
                ->set([
                    'layout' => $newLayoutId,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'id' => $pageId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // change public widgets position
            $this->changePublicWidgetsPosition($pageId, $newLayoutId, $defaultPosition);

            // clear cache
            $this->clearLanguageSensitivePageCaches();
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit page event
        PageEvent::fireEditPageEvent($pageId);
        return true;
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
     *      integer xml_map optional
     *      string xml_map_update optional
     *      float xml_map_priority optional
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

            // change public widgets position
            if ($page['layout'] != $formData['layout']) {
                if (false !== ($layout = $this->getPageLayout($formData['layout']))) {
                    $this->changePublicWidgetsPosition($page['id'], $formData['layout'], $layout['default_position']);
                }
            }

            // clear cache
            $this->clearLanguageSensitivePageCaches();
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
     * Update structure page edited date
     *
     * @param integer @pageId
     * @return void
     */
    protected function updateStructurePageEditedDate($pageId)
    {
        // update the page's date edited
        $update = $this->update()
            ->table('page_structure')
            ->set([
                'date_edited' => date('Y-m-d')
            ])
            ->where([
                'id' => $pageId
            ]);

        $statement = $this->prepareStatementForSqlObject($update);
        $statement->execute();
    }

    /**
     * Change public widget position
     *
     * @param array $oldConnectionInfo
     *      integer id
     *      integer page_id
     *      integer position_id
     *      integer page_layout
     *      integer order
     *      integer widget_id
     * @param integer $newOrder
     * @param integer $newPositionId
     * @return boolean|string
     */
    public function changePublicWidgetPosition(array $oldConnectionInfo, $newOrder, $newPositionId)
    {
        if ($newOrder == $oldConnectionInfo['order']
                && $newPositionId == $oldConnectionInfo['position_id']) {

            return true;
        }

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $this->updateStructurePageEditedDate($oldConnectionInfo['page_id']);

            // move a widget to another position
            if ($newPositionId != $oldConnectionInfo['position_id']) {
                $update = $this->update()
                    ->table('page_widget_connection')
                    ->set([
                        'order' => new Expression($this->adapter->platform->quoteIdentifier('order') . ' + 1')
                    ])
                    ->where([
                        'page_id' => $oldConnectionInfo['page_id'],
                        'position_id' => $newPositionId
                    ]);

                $update->where->greaterThanOrEqualTo('order', $newOrder);
                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();

                $update = $this->update()
                    ->table('page_widget_connection')
                    ->set([
                        'order' => new Expression($this->adapter->platform->quoteIdentifier('order') . ' - 1')
                    ])
                    ->where([
                        'page_id' => $oldConnectionInfo['page_id'],
                        'position_id' => $oldConnectionInfo['position_id']
                    ]);

                $update->where->greaterThanOrEqualTo('order', $oldConnectionInfo['order']);
                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }
            else {
                // get move direction
                $queryExpression = $newOrder < $oldConnectionInfo['order']
                    ? new Expression($this->adapter->platform->quoteIdentifier('order') . ' + 1')
                    : new Expression($this->adapter->platform->quoteIdentifier('order') . ' - 1');

                $update = $this->update()
                    ->table('page_widget_connection')
                    ->set([
                        'order' => $queryExpression
                    ])
                    ->where([
                        'page_id' => $oldConnectionInfo['page_id'],
                        'position_id' => $newPositionId
                    ]);

                    if ($newOrder < $oldConnectionInfo['order']) {
                        $update->where->greaterThanOrEqualTo('order', $newOrder);
                        $update->where->lessThanOrEqualTo('order', $oldConnectionInfo['order']);
                    }
                    else {
                        $update->where->greaterThanOrEqualTo('order', $oldConnectionInfo['order']);
                        $update->where->lessThanOrEqualTo('order', $newOrder);
                    }

                    $statement = $this->prepareStatementForSqlObject($update);
                    $statement->execute();
            }

            // change the widget's position
            $update = $this->update()
                ->table('page_widget_connection')
                ->set([
                    'order' => $newOrder,
                    'position_id' => $newPositionId
                ])
                ->where([
                    'id' => $oldConnectionInfo['id']
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear cache
            $this->clearLanguageSensitivePageCaches();
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        PageEvent::fireChangeWidgetPositionEvent($oldConnectionInfo['widget_id'], $oldConnectionInfo['page_id']);
        return true;
    }

    /**
     * Add public widget
     *
     * @param integer $pageId
     * @param integer $widgetId
     * @param integer $layoutPosition
     * @param integer $widgetlayout
     * @return integer|string
     */
    public function addPublicWidget($pageId, $widgetId, $layoutPosition, $widgetlayout)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $this->updateStructurePageEditedDate($pageId);

            $insert = $this->insert()
                ->into('page_widget_connection')
                ->values([
                    'page_id' => $pageId,
                    'widget_id' => $widgetId,
                    'position_id' => $layoutPosition,
                    'layout' => $widgetlayout,
                    'order' => $this->getMaxPublicWidgetOrder($pageId, $layoutPosition)
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $connectionId = $this->adapter->getDriver()->getLastGeneratedValue();

            // clear cache
            $this->clearLanguageSensitivePageCaches();
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        PageEvent::fireAddWidgetEvent($widgetId, $pageId);
        return $connectionId;
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
     *      integer xml_map optional
     *      string xml_map_update optional
     *      float xml_map_priority optional
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

            // clear cache
            $this->clearLanguageSensitivePageCaches();
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
            // clear caches
            $this->clearLanguageSensitivePageCaches();
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
                'xml_map',
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
                'xml_map' =>  $page->xml_map,
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
     * Get dependent public widgets
     *
     * @param integer $pageId
     * @param integer $widgetId
     * @return array
     */
    public function getDependentPublicWidgets($pageId, $widgetId)
    {
        $definedWidgetsIds = [
            $widgetId
        ];

        $widgetsFilter = $definedWidgetsIds;

        // get all dependent widgets
        if ($widgetsFilter) {
            while (true) {
                $select = $this->select();
                $select->from(['a' => 'page_widget_depend'])
                    ->columns([
                        'widget_id',
                        'depend_widget_id',
                    ])
                    ->join(
                        ['b' => 'page_widget'],
                        new Expression('b.id = a.depend_widget_id and b.type = ?', [self::WIDGET_TYPE_PUBLIC]),                        
                        []
                    )
                    ->join(
                        ['c' => 'page_widget_connection'],
                        new Expression('c.page_id  = ? and c.widget_id = b.id', [$pageId]),                        
                        [],
                        'left'
                    )
                    ->where->in('a.widget_id', $widgetsFilter)
                    ->where->isNull('c.id');

                $statement = $this->prepareStatementForSqlObject($select);
                $resultSet = new ResultSet;
                $resultSet->initialize($statement->execute());

                $widgetsFilter = [];                
                foreach ($resultSet as $widget) {                    
                    if (!in_array($widget['depend_widget_id'], $definedWidgetsIds)) {
                        $widgetsFilter[] = $definedWidgetsIds[] = $widget['depend_widget_id'];                        
                    }
                }

                if (!$widgetsFilter) {
                    break;
                }
            }
        }

        return array_reverse($definedWidgetsIds);
    }

    /**
     * Get widget position info
     *
     * @param string $positionName
     * @param integer $layoutId
     * @return array|boolean
     */
    public function getWidgetPositionInfo($positionName, $layoutId)
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget_position'])
            ->columns([
                'id'
            ])
            ->join(
                ['b' => 'page_widget_position_connection'],
                new Expression('b.position_id = a.id and b.layout_id = ?', [$layoutId]),
                []
            )
            ->where([
                'name' => $positionName
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return  $result->current() ? $result->current() : false;
    }

    /**
     * Get widget connection info
     *
     * @param integer $connectionId
     * @return array|boolean
     */
    public function getWidgetConnectionInfo($connectionId)
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget_connection'])
            ->columns([
                'id',
                'page_id',
                'position_id',
                'widget_id',
                'order'
            ])
            ->join(
                ['b' => 'page_structure'],
                'a.page_id = b.id',
                [
                    'page_layout' => 'layout'
                ],
                'left'
            )
            ->where([
                'a.id' => $connectionId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return  $result->current() ? $result->current() : false;
    }

    /**
     * Get system pages public widgets
     *
     * @param array $pagesIds
     * @return array
     */
    protected function getDependentSystemPagesPublicWidgets($pagesIds)
    {
        $widgets = [];
        $definedWidgetsIds = [];

        // get dependent public widgets
        $select = $this->select();
        $select->from(['a' => 'page_system_widget_depend'])
            ->columns([
                'page_id',
                'widget_id'
            ])
            ->join(
                ['b' => 'page_widget'],
                new Expression('a.widget_id = b.id and b.type = ?', [self::WIDGET_TYPE_PUBLIC]),                        
                []
            )
            ->order('order')
            ->where->in('a.page_id', $pagesIds);

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

        // get all dependent public widgets
        if ($widgetsFilter) {
            while (true) {
                $select = $this->select();
                $select->from(['a' => 'page_widget_depend'])
                    ->columns([
                        'widget_id',
                        'depend_widget_id'
                    ])
                    ->join(
                        ['b' => 'page_widget'],
                        new Expression('b.id = a.depend_widget_id and b.type = ?', [self::WIDGET_TYPE_PUBLIC]),                        
                        []
                    )
                    ->where->in('a.widget_id', $widgetsFilter);

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
                    $this->getDependentSystemPagesPublicWidgets(array_keys($systemPagesMap)))) {

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
     * Get public widget info
     *
     * @param integer $pageId
     * @param integer $widgetId
     * @param intger $systemPageId
     * @return boolean|array
     */
    public function getPublicWidgetInfo($pageId, $widgetId, $systemPageId = null)
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget'])
            ->columns([
                'id',
                'description'
            ])
            ->join(
                ['b' => 'page_widget_page_depend'],
                'a.id = b.widget_id',
                [],
                'left'
            )
            ->join(
                ['c' => 'page_widget_connection'],
                new Expression('c.page_id = ? and a.id = c.widget_id', [$pageId]),
                [],
                'left'
            )
            ->group('a.id')
            ->order('a.id')
            ->where([
                'a.id' => $widgetId,
                'a.type' => self::WIDGET_TYPE_PUBLIC
            ])
            ->where
            ->nest
                ->isNull('c.id')
                ->or
                ->isNotNull('c.id')
                ->and
                ->equalTo('a.duplicate', self::WIDGET_DUPLICATE)
            ->unnest;

        if ($systemPageId) {
            // don't show hidden widgets
            $select->join(
                ['d' => 'page_system_widget_hidden'],
                new Expression('d.page_id = ? and a.id = d.widget_id', [$systemPageId]),
                [],
                'left'
            );
            $select->where->isNull('d.id');

            $select->where([ // we need only specific widgets for the page or not specified         
                new Predicate\PredicateSet([
                        new Predicate\Operator('b.page_id', '=', $systemPageId),
                        new Predicate\isNull('b.id')
                    ],
                    Predicate\PredicateSet::COMBINED_BY_OR
                )
            ]);
        }
        else {
            $select->where->isNull('b.id');
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return  $result->current() ? $result->current() : false;   
    }

    /**
     * Get public widgets
     *
     * @param integer $pageId
     * @param integer $systemPageId
     * @param integer $page
     * @param integer $perPage
     * @param array $filters
     *      array modules
     * @return object Paginator
     */
    public function getPublicWidgets($pageId, $systemPageId = null, $page = 1, $perPage = 0, array $filters = [])
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget'])
            ->columns([
                'id',
                'description'
            ])
            ->join(
                ['b' => 'page_widget_page_depend'],
                'a.id = b.widget_id',
                [],
                'left'
            )
            ->join(
                ['c' => 'page_widget_connection'],
                new Expression('c.page_id = ? and a.id = c.widget_id', [$pageId]),
                [],
                'left'
            )
            ->group('a.id')
            ->order('a.id')
            ->where([
                'a.type' => self::WIDGET_TYPE_PUBLIC
            ])
            ->where
            ->nest
                ->isNull('c.id')
                ->or
                ->isNotNull('c.id')
                ->and
                ->equalTo('a.duplicate', self::WIDGET_DUPLICATE)
            ->unnest;

        if ($systemPageId) {
            // don't show hidden widgets
            $select->join(
                ['d' => 'page_system_widget_hidden'],
                new Expression('d.page_id = ? and a.id = d.widget_id', [$systemPageId]),
                [],
                'left'
            );
            $select->where->isNull('d.id');

            $select->where([ // we need only specific widgets for the page or not specified         
                new Predicate\PredicateSet([
                        new Predicate\Operator('b.page_id', '=', $systemPageId),
                        new Predicate\isNull('b.id')
                    ],
                    Predicate\PredicateSet::COMBINED_BY_OR
                )
            ]);
        }
        else {
            $select->where->isNull('b.id');
        }

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
     * @return object Paginator
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
                [],
                'left'
            )
            ->join(
                ['d' => 'page_widget'],
                new Expression('c.widget_id = d.id and d.type = ?', [self::WIDGET_TYPE_PUBLIC]),
                [
                    'widgets' => new Expression('count(d.id)') 
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