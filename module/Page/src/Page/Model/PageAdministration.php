<?php
namespace Page\Model;

use Acl\Service\Acl as AclService;
use Application\Utility\ApplicationErrorLogger;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Utility\ApplicationSlug as SlugUtility;
use Page\Model\PageNestedSet;
use Page\Event\PageEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
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
            'footer_menu_order' => 0,
            'xml_map_priority' => 0
        ];

        $basicFields = [
            'slug',
            'module',
            'layout',
            'title',
            'meta_description',
            'meta_keywords',
            'meta_robots',
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
     *      string meta_robots optional
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
                        'slug' => $this->generatePageSlug($page['id'], $formData['title'])
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
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // get list of all active widgets
            $select = $this->select();
            $select->from(['a' => 'page_widget_connection'])
                ->columns([
                    'order'
                ])
                ->join(
                    ['b' => 'page_widget'],
                    'a.widget_id = b.id',
                    []
                )
                ->join(
                    ['c' => 'application_module'],
                    new Expression('b.module = c.id and c.status = ?', [self::MODULE_STATUS_ACTIVE]),
                    []
                )
                ->join(
                    ['d' => 'page_structure'],
                    new Expression('d.system_page = b.depend_page_id and d.language = ? and d.active = ?', [
                        $this->getCurrentLanguage(),
                        PageNestedSet::PAGE_STATUS_ACTIVE
                    ]),
                    [],
                    'left'
                )
                ->order('a.order')
                ->where([
                    'a.page_id' => $oldConnectionInfo['page_id'],
                    'a.position_id' => $newPositionId
                ])
                ->where->nest
                    ->isNull('b.depend_page_id')
                    ->or
                    ->isNotNull('b.depend_page_id')
                    ->and
                    ->isNotNull('d.system_page')
                ->unnest;

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            $index = 1;
            $activeWidgets = [];
            foreach ($result as $activeWidget) {
                $activeWidgets[$index] = $activeWidget['order'];
                $index++;
            }

            // change widget order value
            if ($activeWidgets) {
                $newOrder = isset($activeWidgets[$newOrder])
                    ? $activeWidgets[$newOrder]
                    : $activeWidgets[count($activeWidgets)] + 1;
            }

            if ($newOrder == $oldConnectionInfo['order']
                    && $newPositionId == $oldConnectionInfo['position_id']) {

                return true;
            }

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
     * Delete widget connection
     *
     * @param array $widget
     *      integer id
     *      integer page_id
     *      integer position_id
     *      integer widget_id
     *      integer order
     * @return boolean|string
     */
    public function deleteWidgetConnection(array $widget)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $this->updateStructurePageEditedDate($widget['page_id']);

            $delete = $this->delete()
                ->from('page_widget_connection')
                ->where([
                    'id' => $widget['id']
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // move up next widgets
            $update = $this->update()
                ->table('page_widget_connection')
                ->set([
                    'order' => new Expression($this->adapter->platform->quoteIdentifier('order') . ' - 1')
                ])
                ->where([
                    'page_id' => $widget['page_id'],
                    'position_id' => $widget['position_id']
                ]);
            $update->where->greaterThan('order', $widget['order']);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear caches
            $this->clearLanguageSensitivePageCaches();
            $this->clearWidgetsSettingsCache($widget['page_id']);
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        PageEvent::fireDeleteWidgetEvent($widget['widget_id'], $widget['page_id']);
        return true;
    }

    /**
     * Add public widget
     *
     * @param integer $pageId
     * @param integer $widgetId
     * @param integer $layoutPosition
     * @return integer|string
     */
    public function addPublicWidget($pageId, $widgetId, $layoutPosition)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $this->updateStructurePageEditedDate($pageId);

            $widgetLayout = SettingService::getSetting('page_new_widgets_layout');

            $insert = $this->insert()
                ->into('page_widget_connection')
                ->values([
                    'page_id' => $pageId,
                    'widget_id' => $widgetId,
                    'position_id' => $layoutPosition,
                    'layout' => (int) $widgetLayout ? $widgetLayout : null,
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
     *      string meta_robots optional
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
                        'slug' => $this->generatePageSlug($pageId, $page['title'])
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
                'disable_user_menu',
                'disable_menu',
                'disable_site_map',
                'disable_footer_menu',
                'disable_xml_map',
                'forced_visibility'
            ])
            ->join(
                ['d' => 'page_structure'],
                new Expression('a.slug = d.slug and d.language = ?', [$this->getCurrentLanguage()]),
                [],
                'left'
            )
            ->join(
                ['i' => 'application_module'],
                new Expression('i.id = a.module and i.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->where->in('a.id', $pagesIds)
            ->where->isNull('d.id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        // get home page
        $homePage = $this->serviceLocator->get('Config')['home_page'];

        // get default values
        $defaultPageLayout = $this->getPageLayout(SettingService::getSetting('page_new_pages_layout'));
        $defaultWidgetLayout = SettingService::getSetting('page_new_widgets_layout');
        $defaultShowInMainMenu = (int) SettingService::getSetting('page_new_pages_in_main_menu');
        $defaultShowInSiteMap = (int) SettingService::getSetting('page_new_pages_in_site_map');
        $defaultShowInFooterMenu = (int) SettingService::getSetting('page_new_pages_in_footer_menu');
        $defaultShowInUserMenu = (int) SettingService::getSetting('page_new_pages_in_user_menu');
        $defaultShowInXmlMap = (int) SettingService::getSetting('page_new_pages_in_xml_map');
        $defaultPageVisibility = SettingService::getSetting('page_new_pages_hidden_for');
        $defaultPageVisibility = SettingService::getSetting('page_new_pages_hidden_for');

        // check the roles
        if ($defaultPageVisibility) {
            // get all ACL roles
            $aclRoles = AclService::getAclRoles(false, true);

            // compare them with a setting value
            foreach ($defaultPageVisibility as $index => $roleId) {
                if (!array_key_exists($roleId, $aclRoles)) {
                    unset($defaultPageVisibility[$index]);
                }
            }
        }

        foreach ($resultSet as $page) {
            $dependentPagesFilter[] = $page->id;
            $pages[$page->id] = [
                'slug' =>  $page->slug,
                'module' =>  $page->module,
                'visibility_settings' => !$page->forced_visibility && $defaultPageVisibility ? $defaultPageVisibility : null,
                'user_menu' =>  !$page->disable_user_menu && $defaultShowInUserMenu ? 1 : null,
                'user_menu_order' =>  (int) SettingService::getSetting('page_new_pages_user_menu_order'),
                'menu' =>  !$page->disable_menu && $defaultShowInMainMenu || $page->slug == $homePage ? 1 : null,
                'site_map' =>  !$page->disable_site_map && $defaultShowInSiteMap || $page->slug == $homePage ? 1 : null,
                'xml_map' => !$page->disable_xml_map && $defaultShowInXmlMap ? 1 : null,
                'xml_map_update' => SettingService::getSetting('page_new_pages_xml_map_update'),
                'xml_map_priority' => SettingService::getSetting('page_new_pages_xml_map_priority'),
                'footer_menu' =>  !$page->disable_footer_menu && $defaultShowInFooterMenu ? 1 : null,
                'footer_menu_order' =>  (int) SettingService::getSetting('page_new_pages_footer_menu_order'),
                'layout' =>  !empty($defaultPageLayout['id']) ? $defaultPageLayout['id'] : null,
                'layout_default_position' =>  !empty($defaultPageLayout['default_position']) ? $defaultPageLayout['default_position'] : null,
                'widget_default_layout' => $defaultWidgetLayout ? $defaultWidgetLayout : null,
                'order' => $order,
                'system_page' => $page->id,
                'active' => (int) SettingService::getSetting('page_new_pages_active')
                    ? PageNestedSet::PAGE_STATUS_ACTIVE
                    : null
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
     * Save widget settings
     *
     * @param integer $widgetId
     * @param integer $pageId
     * @param integer $widgetConnectionId
     * @param array $settingsList
     * @param array $formData
     * @return boolean|string
     */
    public function saveWidgetSettings($widgetId, $pageId, $widgetConnectionId, array $settingsList, array $formData)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $this->updateStructurePageEditedDate($pageId);

            // update primary widget settings
            $update = $this->update()
                ->table('page_widget_connection')
                ->set([
                    'title' => !empty($formData['title']) ? $formData['title'] : null,
                    'layout' => !empty($formData['layout']) ? $formData['layout'] : null
                ])
                ->where([
                    'id' => $widgetConnectionId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // update extra widget settings
            foreach ($settingsList as $setting) {
                if (array_key_exists($setting['name'], $formData)) {
                    // remove previously value
                    $query = $this->delete('page_widget_setting_value')
                        ->where([
                            'setting_id' => $setting['id'],
                            'widget_connection' => $widgetConnectionId
                        ]);

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();

                    $value = is_array($formData[$setting['name']])
                        ? implode(PageWidgetSetting::SETTINGS_ARRAY_DEVIDER, $formData[$setting['name']])
                        : (null != $formData[$setting['name']] ? $formData[$setting['name']] : '');

                    $query = $this->insert('page_widget_setting_value')
                        ->values([
                           'setting_id' => $setting['id'],
                           'value' => $value,
                           'widget_connection' => $widgetConnectionId
                        ]);

                    $statement = $this->prepareStatementForSqlObject($query);
                    $statement->execute();
                }
            }

            // clear all widget old visibility settings
            $delete = $this->delete()
                ->from('page_widget_visibility')
                ->where([
                    'widget_connection' => $widgetConnectionId
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // add new widget visibility settings
            if (!empty($formData['visibility_settings'])) {
                foreach ($formData['visibility_settings'] as $aclRoleId) {
                    $insert = $this->insert()
                        ->into('page_widget_visibility')
                        ->values([
                            'widget_connection' => $widgetConnectionId,
                            'hidden' => $aclRoleId
                        ]);

                    $statement = $this->prepareStatementForSqlObject($insert);
                    $statement->execute();
                }
            }

            // clear caches
            $this->clearLanguageSensitivePageCaches();
            $this->clearWidgetsSettingsCache($pageId);
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        PageEvent::fireEditWidgetSettingsEvent($widgetId, $pageId);
        return true;
    }

    /**
     * Get widget connection info
     *
     * @param integer $connectionId
     * @param boolean $visibilitySettings
     * @return array|boolean
     */
    public function getWidgetConnectionInfo($connectionId, $visibilitySettings = false)
    {
        // check widgets dependents
        $dependentCheckSelect = $this->select();
        $dependentCheckSelect->from(['c' => 'page_widget_depend'])
            ->columns([])
            ->join(
                ['d' => 'page_widget_connection'],
                ('d.widget_id = c.depend_widget_id'),
                [
                    'id'
                ]
            )
            ->where(['a.widget_id' => new Expression('c.widget_id')])
            ->where(['a.page_id' => new Expression('d.page_id')])
            ->limit(1);

        $select = $this->select();
        $select->from(['a' => 'page_widget_connection'])
            ->columns([
                'id',
                'widget_title' => 'title',
                'layout',
                'page_id',
                'position_id',
                'widget_id',
                'order',
                'widget_depend_connection_id' => new Expression('(' . $this->getSqlStringForSqlObject($dependentCheckSelect) . ')')
            ])
            ->join(
                ['b' => 'page_structure'],
                new Expression('a.page_id = b.id and b.language = ?', [$this->getCurrentLanguage()]),                
                [
                    'page_layout' => 'layout'
                ]
            )
            ->join(
                ['i' => 'page_widget'],
                'a.widget_id = i.id',
                [
                    'widget_description' => 'description',
                    'widget_forced_visibility' => 'forced_visibility'
                ]
            )
            ->join(
                ['f' => 'page_system_widget_depend'],
                'b.system_page = f.page_id and f.widget_id = a.widget_id',
                [
                    'widget_page_depend_connection_id' => 'id'
                ],
                'left'
            )
            ->where([
                'a.id' => $connectionId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $widget = $result->current() ? $result->current() : false;

        // get visibility settings
        if (false !== $widget && $visibilitySettings) {
            $select = $this->select();
            $select->from('page_widget_visibility')
                ->columns([
                    'hidden'
                ])
                ->where([
                    'widget_connection' => $connectionId
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            foreach($result as $visibility) {
                $widget['visibility_settings'][] = $visibility['hidden'];
            }
        }

        return $widget;
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
     * Get dependent widgets
     *
     * @param integer $widgetId
     * @return object ResultSet
     */
    public function getDependentWidgets($widgetId, $pageId)
    {
        $select = $this->select();
        $select->from(['a' => 'page_widget_depend'])
            ->columns([
            ])
            ->join(
                ['b' => 'page_widget_connection'],
                new Expression('b.widget_id = a.depend_widget_id and b.page_id = ?', [$pageId]),
                [
                    'widget_title' => 'title'
                ]
            )
            ->join(
                ['c' => 'page_widget'],
                'b.widget_id = c.id',
                [
                    'widget_description' => 'description'
                ]
            )
            ->where([
                'a.widget_id' => $widgetId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet;
    }

    /**
     * Get dependent pages
     *
     * @param integer $pageId
     * @return object ResultSet
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
                [
                    'title'
                ]
            )
            ->join(
                ['c' => 'page_system'],
                'b.system_page = c.id',
                [
                    'system_title' => 'title'
                ]
            );
        }
        else {            
            $select->join(
                ['b' => 'page_system'],
                'a.page_id = b.id',
                [
                    'system_title' => 'title'
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
     *      array modules
     *      string slug
     * @return object
     */
    public function getSystemPages($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'slug'
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
                'title',
                'slug'
            ])
            ->join(
                ['b' => 'application_module'],
                new Expression('b.id = a.module and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'module_name' => 'name'
                ]
            )
            ->join(
                ['c' => 'page_structure'],
                new Expression('a.slug = c.slug and c.language = ? and c.type = ?', [
                    $this->getCurrentLanguage(),
                    PageNestedSet::PAGE_TYPE_SYSTEM
                ]),
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
            ->join(
                ['i' => 'page_structure'],
                new Expression('a.slug = i.slug and i.language = ?', [$this->getCurrentLanguage()]),
                [
                    'structure_slug' => 'slug'
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

        // filter by slug
        if (!empty($filters['slug'])) {
            $select->where([
                'a.slug' => $filters['slug']
            ]);
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
            ->join(
                ['d' => 'application_module'],
                new Expression('d.id = a.module and d.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->join(
                ['i' => 'page_structure'],
                new Expression('i.system_page = a.depend_page_id and i.language = ? and i.active = ?', [
                    $this->getCurrentLanguage(),
                    PageNestedSet::PAGE_STATUS_ACTIVE
                ]),
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
            ->unnest
            ->nest
                ->isNull('a.depend_page_id')
                ->or
                ->isNotNull('a.depend_page_id')
                ->and
                ->isNotNull('i.system_page')
            ->unnest;

        if ($systemPageId) {
            // don't show hidden widgets
            $select->join(
                ['f' => 'page_system_widget_hidden'],
                new Expression('f.page_id = ? and a.id = f.widget_id', [$systemPageId]),
                [],
                'left'
            );
            $select->where->isNull('f.id');

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
     *      array modules
     *      string status
     *      string slug
     * @return object Paginator
     */
    public function getStructurePages($parentId = null, $page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'id',
            'position',
            'slug',
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
                'slug',
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
                [],
                'left'
            )
            ->join(
                ['dd' => 'page_structure'],
                new Expression('dd.system_page = d.depend_page_id and dd.language = ? and dd.active = ?', [
                    $this->getCurrentLanguage(),
                    PageNestedSet::PAGE_STATUS_ACTIVE
                ]),
                [],
                'left'
            )
            ->join(
                ['i' => 'application_module'],
                new Expression('i.id = d.module and i.status = ? and (d.depend_page_id is null
                        or d.depend_page_id is not null and dd.system_page is not null)', [self::MODULE_STATUS_ACTIVE]),
                [
                    'widgets' => new Expression('count(i.id)') 
                ],
                'left'
            )
            ->join(
                ['f' => 'application_module'],
                new Expression('f.id = a.module and f.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType)
            ->where([
                'a.language' => $this->getCurrentLanguage()
            ]);

        null === $parentId
            ? $select->where->isNull('a.parent_id')
            : $select->where(['a.parent_id' => $parentId]);

        // filter by modules
        if (!empty($filters['modules']) && is_array($filters['modules'])) {
            $select->where->in('f.id', $filters['modules']);
        }

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

        // filter by slug
        if (!empty($filters['slug'])) {
            $select->where([
                'a.slug' => $filters['slug']
            ]);
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Generate page slug
     *
     * @param integer $pageId
     * @param string $title
     * @param string $spaceDevider
     * @return string
     */
    public function generatePageSlug($pageId, $title, $spaceDevider = '-')
    {
        // generate a slug
        $newSlug  = $slug = SlugUtility::slugify($title, self::PAGE_SLUG_LENGTH, $spaceDevider);
        $slagSalt = null;

        while (true) {
            // check the slug existent in structure pages 
            $select = $this->select();
            $select->from('page_structure')
                ->columns([
                    'slug'
                ])
                ->where([
                    'slug' => $newSlug,
                    'language' => $this->getCurrentLanguage()
                ]);

            $select->where([
                new NotInPredicate('id', [$pageId])
            ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $structurePagesResultSet = new ResultSet;
            $structurePagesResultSet->initialize($statement->execute());

            // check the slug existent in system pages
            $select = $this->select();
            $select->from('page_system')
                ->columns([
                    'slug'
                ])
                ->where([
                    'slug' => $newSlug
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $systemPagesResultSet = new ResultSet;
            $systemPagesResultSet->initialize($statement->execute());

            // generated slug not found
            if (!$structurePagesResultSet->current() && !$systemPagesResultSet->current()) {
                break;
            }

            $newSlug = $pageId . $spaceDevider . $slug . $slagSalt;

            // add an extra slug
            $slagSalt = $spaceDevider . SlugUtility::generateRandomSlug($this->slugSaltLength); // add a salt
        }

        return $newSlug;
    }
}