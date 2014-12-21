<?php
namespace Page\Model;

use Application\Model\ApplicationAbstractBase;
use Localization\Service\Localization as LocalizationService;
use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;

class PageBase extends ApplicationAbstractBase
{
    /**
     * Cache widgets connections
     */
    const CACHE_WIDGETS_CONNECTIONS = 'Page_Widgets_Connections_';

    /**
     * Cache widgets settings
     */
    const CACHE_WIDGETS_SETTINGS_BY_PAGE = 'Page_Widgets_Settings_By_Page_';

    /**
     * Cache pages map
     */
    const CACHE_PAGES_MAP = 'Page_Pages_Map';

    /**
     * Cache pages tree
     */
    const CACHE_PAGES_TREE = 'Page_Pages_Tree_';

    /**
     * Cache footer menu
     */
    const CACHE_FOOTER_MENU = 'Page_Footer_Menu_';

    /**
     * Cache user menu
     */
    const CACHE_USER_MENU = 'Page_User_Menu_';

    /**
     * Pages data cache tag
     */
    const CACHE_PAGES_DATA_TAG = 'Page_Data_Tag';

    /**
     * Page slug lengh
     */
    const PAGE_SLUG_LENGTH = 40;

    /**
     * Public widget type
     */
    const WIDGET_TYPE_PUBLIC = 'public';

    /**
     * System widget type
     */
    const WIDGET_TYPE_SYSTEM = 'system';

    /**
     * Default widget layout
     */
    const DEFAULT_WIDGET_LAYOUT = 1;

    /**
     * Widget duplicate
     */
    const WIDGET_DUPLICATE = 1;

    /**
     * Pages map
     * @var array
     */
    protected static $pagesMap = null;

    /**
     * Pages tree
     * @var array
     */
    protected static $pagesTree = [];

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
            $this->pageModel = $this->serviceLocator->get('Page\Model\PageNestedSet');
        }

        return $this->pageModel;
    }

    /**
     * Get all page structure children
     *
     * @param integer $pageId
     * @param boolean $active
     * @return array|boolean
     */
    public function getAllPageStructureChildren($pageId, $active = false)
    {
        return $this->getPageModel()->getAllPageChildren($pageId, $active);
    }

    /**
     * Get current language
     *
     * @return string
     */
    public function getCurrentLanguage()
    {
       return LocalizationService::getCurrentLocalization()['language']; 
    }

    /**
     * Get widgets connections
     *
     * @param string $language
     * @return array
     */
    public function getWidgetsConnections($language)
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_WIDGETS_CONNECTIONS . $language);

        // check data in cache
        if (null === ($widgetConnections = $this->staticCacheInstance->getItem($cacheName))) {
            // get widgets visibility
            $select = $this->select();
            $select->from(['a' => 'page_widget_visibility'])
                ->columns([
                    'hidden',
                    'widget_connection'
                ])
                ->join(
                    ['b' => 'page_widget_connection'],
                    'b.id = a.widget_connection',
                    []
                )
                ->join(
                    ['c' => 'page_structure'],
                    new Expression('b.page_id = c.id and c.language = ?', [$language]),
                    []
                );

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $visibilityOptions = [];
            foreach ($resultSet as $visibility) {
                $visibilityOptions[$visibility->widget_connection][] = $visibility->hidden;
            }

            // check widgets dependents
            $dependentCheckSelect = $this->select();
            $dependentCheckSelect->from(['j' => 'page_widget_depend'])
                ->columns([])
                ->join(
                    ['h' => 'page_widget_connection'],
                    ('h.widget_id = j.depend_widget_id'),
                    [
                        'id'
                    ]
                )
                ->where(['a.widget_id' => new Expression('j.widget_id')])
                ->where(['a.page_id' => new Expression('h.page_id')])
                ->limit(1);

            // get widgets connections
            $select = $this->select();
            $select->from(['a' => 'page_widget_connection'])
                ->columns([
                    'widget_title' => 'title',
                    'widget_connection_id' => 'id',
                    'widget_id',
                    'widget_depend_connection_id' => new Expression('(' . $this->getSqlStringForSqlObject($dependentCheckSelect) . ')')
                ])
                ->join(
                    ['b' => 'page_structure'],
                    new Expression('a.page_id = b.id and b.language = ?', [$language]),
                    [
                        'page_id' => 'id'
                    ],
                    'left'
                )
                ->join(
                    ['c' => 'page_widget'],
                    'a.widget_id = c.id',
                    [
                        'widget_name' => 'name',
                        'widget_description' => 'description',
                        'widget_type' => 'type'
                    ]
                )
                ->join(
                    ['cc' => 'page_structure'],
                    new Expression('cc.system_page = c.depend_page_id and cc.language = ? and cc.active = ?', [
                        $this->getCurrentLanguage(),
                        PageNestedSet::PAGE_STATUS_ACTIVE
                    ]),
                    [],
                    'left'
                )
                ->join(
                    ['d' => 'application_module'],
                    new Expression('c.module = d.id and d.status = ?', [self::MODULE_STATUS_ACTIVE]),
                    []
                )->join(
                    ['e' => 'page_widget_position'],
                    'a.position_id = e.id',
                    [
                        'widget_position' => 'name'
                    ]
                )->join(
                    ['f' => 'page_widget_layout'],
                    'a.layout = f.id',
                    [
                        'widget_layout' => 'name'
                    ],
                    'left'
                )->join(
                    ['j' => 'page_system_widget_depend'],
                    'b.system_page = j.page_id and j.widget_id = a.widget_id',
                    [
                        'widget_page_depend_connection_id' => 'id'
                    ],
                    'left'
                )
                ->order('a.order')
                ->where->IsNull('a.page_id')
                    ->or->where->IsNotNull('a.page_id')
                    ->and->where->IsNotNull('b.id')
                ->nest
                    ->isNull('c.depend_page_id')
                    ->or
                    ->isNotNull('c.depend_page_id')
                    ->and
                    ->isNotNull('cc.system_page')
                ->unnest;

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $widgetConnections = [];
            foreach ($resultSet as $connection) {
                $widgetConnections[$connection->page_id][$connection->widget_position][$connection->widget_connection_id] = [
                    'widget_name' => $connection->widget_name,
                    'widget_title' => $connection->widget_title,
                    'widget_id' => $connection->widget_id,
                    'widget_system' => $connection->widget_type == self::WIDGET_TYPE_SYSTEM ? true : false,
                    'widget_description' => $connection->widget_description,
                    'widget_layout' => $connection->widget_layout,
                    'widget_connection_id' => $connection->widget_connection_id,
                    'widget_depend_connection_id' => $connection->widget_depend_connection_id,
                    'widget_page_depend_connection_id' => $connection->widget_page_depend_connection_id,
                    'hidden' => !empty($visibilityOptions[$connection->widget_connection_id])
                        ? $visibilityOptions[$connection->widget_connection_id]
                        : []
                ];
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $widgetConnections);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        return $widgetConnections;
    }

    /**
     * Get page layouts
     *
     * @param boolean $process
     * @return array|object ResultSet
     */
    public function getPageLayouts($process = true)
    {
        $select = $this->select();
        $select->from('page_layout')
            ->columns([
                'id',
                'title',
                'image'
            ])
            ->order('name');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        if ($process) {
            $layouts = [];
            foreach ($resultSet as $layout) {
                $layouts[$layout->id] = $layout->title;
            }
    
            return $layouts;
        }

        return $resultSet;
    }

    /**
     * Get page layout
     *
     * @param integer $layoutId
     * @return array|boolean
     */
    public function getPageLayout($layoutId)
    {
        $select = $this->select();
        $select->from('page_layout')
            ->columns([
                'id',
                'title',
                'name',
                'default_position'
            ])
            ->where([
                'id' => $layoutId
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current()
            ? (array) $resultSet->current()
            : false;
    }

    /**
     * Is page movable
     *
     * @param string $slug
     * @param integer $pageId
     * @return boolean
     */
    public function isPageMovable($leftKey, $rightKey, $level, $parentLeft)
    {
        return $this->getPageModel()->isNodeMovable($leftKey, $rightKey, $level, $parentLeft);
    }

    /**
     * Is slug free
     *
     * @param string $slug
     * @param integer $pageId
     * @return boolean
     */
    public function isSlugFree($slug, $pageId = 0)
    {
        // check the slug in the list of system pages
        $select = $this->select();
        $select->from(['a' => 'page_system'])
            ->columns([
                'id'
            ])
            ->join(
                ['b' => 'application_module'],
                new Expression('a.module = b.id and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->where([
                'slug' => $slug 
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        if (!$resultSet->current()) {
            // check the slug in the pages structure
            $select = $this->select();
            $select->from('page_structure')
                ->columns([
                    'id'
                ])
                ->where([
                    'slug' => $slug,
                    'language' => $this->getCurrentLanguage()
                ]);

            if ($pageId) {
                $select->where([
                    new NotInPredicate('id', [$pageId])
                ]);
            }

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            return $resultSet->current() ? false : true;
        }

        return false;
    }

    /**
     * Clear language sensitive page caches
     *
     * @return boolean
     */
    public function clearLanguageSensitivePageCaches()
    {
        $result = true;
        $languageSensitiveCaches = [
            self::CACHE_USER_MENU,
            self::CACHE_FOOTER_MENU,
            self::CACHE_PAGES_TREE,
            self::CACHE_WIDGETS_CONNECTIONS
        ];

        // clear language sensitive caches
        foreach ($languageSensitiveCaches as $cacheName) {
            $cacheName = CacheUtility::getCacheName($cacheName . $this->getCurrentLanguage());

            if ($this->staticCacheInstance->hasItem($cacheName)) {
                if (false === ($result = $this->staticCacheInstance->removeItem($cacheName))) {
                    return $result;
                }
            }
        }

        // clear the whole pages map
        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_MAP);
        if ($this->staticCacheInstance->hasItem($cacheName)) {
            $result = $this->staticCacheInstance->removeItem($cacheName);
        }

        return $result;
    }

    /**
     * Clear widgets settings cache
     *
     * @param integer $pageId
     * @return boolean
     */
    public function clearWidgetsSettingsCache($pageId)
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_WIDGETS_SETTINGS_BY_PAGE . $pageId);

        // clear a page's widgets settings cache
        if ($this->staticCacheInstance->hasItem($cacheName)) {
            return $this->staticCacheInstance->removeItem($cacheName);
        }

        return false;
    }

    /**
     * Get structure page info
     *
     * @param integer $id
     * @param boolean $useCurrentLanguage
     * @param boolean $defaultHome
     * @param boolean $visibilitySettings
     * @return array|boolean
     */
    public function getStructurePageInfo($id, $useCurrentLanguage = true, $defaultHome = false, $visibilitySettings = false)
    {
        $dependentCheckSelect = $this->select();
        $dependentCheckSelect->from(['b' => 'page_system_page_depend'])
            ->columns([
                'id'
            ])
            ->join(
                ['c' => 'page_structure'],
                new Expression('b.page_id = c.system_page and c.language = ?', [$this->getCurrentLanguage()]),
                []
            )
            ->where(['a.system_page' => new Expression('b.depend_page_id')])
            ->limit(1);

        $select = $this->select();
        $select->from(['a' => 'page_structure'])
            ->columns([
                'id',
                'slug',
                'title',
                'meta_description',
                'meta_keywords',
                'meta_robots',
                'module',
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
                'type',
                'language',
                'layout',
                'redirect_url',
                'left_key',
                'right_key',
                'level',
                'parent_id',
                'system_page',
                'dependent_page' => new Expression('(' . $this->getSqlStringForSqlObject($dependentCheckSelect) . ')')
            ])
            ->join(
                ['d' => 'page_layout'],
                'd.id = a.layout',
                [
                    'layout_default_position' => 'default_position',
                    'layout_name' => 'name'
                ]
            )
            ->join(
                ['i' => 'page_widget_layout'],
                new Expression('i.default  = ?', [PageWidget::DEFAULT_WIDGET_LAYOUT]),                
                [
                    'widget_default_layout' => 'id'
                ]
            )
            ->join(
                ['f' => 'page_system'],
                'f.id = a.system_page',
                [
                    'system_title' => 'title',
                    'disable_menu',
                    'disable_site_map',
                    'disable_xml_map',
                    'disable_footer_menu',
                    'disable_user_menu',
                    'forced_visibility',
                    'disable_seo'
                ],
                'left'
            )
            ->join(
                ['g' => 'application_module'],
                new Expression('g.id = a.module and g.status = ?', [self::MODULE_STATUS_ACTIVE]),
                []
            )
            ->limit(1)
            ->order('a.parent_id desc');

        if ($defaultHome) {
            $select->where([
                new Predicate\PredicateSet([
                        new Predicate\Operator('a.id', '=', $id),
                        new Predicate\Operator('a.slug', '=', $this->serviceLocator->get('Config')['home_page']),
                    ],
                    Predicate\PredicateSet::COMBINED_BY_OR
                )
            ]);
        }
        else {
            $select->where([
                'a.id' => $id
            ]);
        }

        if ($useCurrentLanguage) {
            $select->where([
                'a.language' => $this->getCurrentLanguage()
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        // get visibility settings
        if (null != ($page = $result->current()) && $visibilitySettings) {
            $select = $this->select();
            $select->from('page_visibility')
                ->columns([
                    'hidden'
                ])
                ->where([
                    'page_id' => $page['id']    
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();

            foreach($result as $visibility) {
                $page['visibility_settings'][] = $visibility['hidden'];
            }
        }

        return $page ? $page : false;
    }

    /**
     * Get user menu
     * 
     * @param string $language
     * @return array
     */
    public function getUserMenu($language)
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_USER_MENU . $language);

        // check data in a cache
        if (null === ($userMenu = $this->staticCacheInstance->getItem($cacheName))) {
            // get the user menu
            $select = $this->select();
            $select->from(['a' => 'page_structure'])
                ->columns([
                    'slug',
                    'title',
                    'type'
                ])
                ->join(
                    ['b' => 'page_system'],
                    'b.id = a.system_page',
                    [
                        'system_title' => 'title'
                    ],
                    'left'
                )
                ->where([
                    'a.user_menu' => PageNestedSet::PAGE_IN_USER_MENU,
                    'a.language' => $language
                ])
                ->order('a.user_menu_order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $userMenu = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $userMenu);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        return $userMenu;
    }

    /**
     * Get footer menu
     * 
     * @param string $language
     * @return array
     */
    public function getFooterMenu($language)
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_FOOTER_MENU . $language);

        // check data in a cache
        if (null === ($footerMenu = $this->staticCacheInstance->getItem($cacheName))) {
            // get the footer menu
            $select = $this->select();
            $select->from(['a' => 'page_structure'])
                ->columns([
                    'slug',
                    'title',
                    'type'
                ])
                ->join(
                    ['b' => 'page_system'],
                    'b.id = a.system_page',
                    [
                        'system_title' => 'title'
                    ],
                    'left'
                )
                ->where([
                    'a.footer_menu' => PageNestedSet::PAGE_IN_FOOTER_MENU,
                    'a.language' => $language
                ])
                ->order('a.footer_menu_order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $footerMenu = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $footerMenu);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        return $footerMenu;
    }

    /**
     * Get pages tree
     *
     * @param string $language
     * @return array
     */
    public function getPagesTree($language)
    {
        if (isset(self::$pagesTree[$language])) {
            return self::$pagesTree[$language];
        }

        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_TREE . $language);

        // check data in a cache
        if (null === ($pagesTree = $this->staticCacheInstance->getItem($cacheName))) {
            $pagesTree = [];

            // process pages map
            foreach ($this->getPagesMap($language) as $pageName => $pageOptions) {
                if ($pageOptions['module_status'] != self::MODULE_STATUS_ACTIVE) {
                    continue;
                }

                $this->processPagesTree($pagesTree, $pageName, $pageOptions);
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $pagesTree);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        self::$pagesTree[$language] = $pagesTree;
        return $pagesTree;
    }

    /**
     * Process pages tree
     *
     * @param array $pagesTree
     * @param string $currentPageName
     * @param array $currentPageOptions
     * @return void
     */
    protected function processPagesTree(array &$pages, $currentPageName, array $currentPageOptions)
    {
        if (empty($currentPageOptions['parent'])) {
            $pages[$currentPageName] = $currentPageOptions;
            return;
        }

        // searching for a parent
        foreach ($pages as $pageName => &$pageOptions) {
            if ($currentPageOptions['parent'] == $pageName) {
                $pages[$pageName]['children'][$currentPageName] = $currentPageOptions;
                return;
            }

            // checking for children
            if (!empty($pageOptions['children'])) {
                $this->processPagesTree($pageOptions['children'], $currentPageName, $currentPageOptions);
            }
        }
    }

    /**
     * Get pages map
     * 
     * @param string $language
     * @return array
     */
    public function getPagesMap($language = null)
    {
        $pagesMap = $this->getAllPagesMap();

        return $language 
            ? (isset($pagesMap[$language]) ? $pagesMap[$language] : [])
            : $pagesMap;
    }

    /**
     * Get all pages map
     * 
     * @return array
     */
    public function getAllPagesMap()
    {
        if (null !== self::$pagesMap) {
            return self::$pagesMap;
        }

        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_MAP);

        // check data in a cache
        if (null === ($pagesMap = $this->staticCacheInstance->getItem($cacheName))) {
            // get all pages visibility
            $pagesVisibility = [];
            $select = $this->select();
            $select->from(['a' => 'page_visibility'])
                ->columns([
                    'page_id',
                    'hidden'
                ])
                ->join(
                    ['b' => 'page_structure'],
                    'b.id = a.page_id',
                    [
                        'slug'
                    ]
                );

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // process pages visibility
            foreach ($resultSet as $pageVisibility) {
                $pagesVisibility[$pageVisibility->page_id][] = $pageVisibility->hidden;
            }

            // get all pages structure
            $select = $this->select();
            $select->from(['a' => 'page_structure'])
                ->columns([
                    'id',
                    'slug',
                    'title',
                    'level',
                    'active',
                    'site_map',
                    'xml_map',
                    'menu',
                    'type',
                    'language',
                    'date_edited',
                    'xml_map_update',
                    'xml_map_priority'
                ])
                ->join(
                    ['b' => 'page_system'],
                    'b.id = a.system_page',
                    [
                        'privacy',
                        'pages_provider',
                        'system_title' => 'title'
                    ],
                    'left'
                )
                ->join(
                    ['c' => 'application_module'],
                    'c.id = a.module',
                    [
                        'module_status' => 'status'
                    ]
                )
                ->order('a.language, a.left_key');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $language = null;
            $pagesMap = [];

            // process pages
            foreach ($resultSet as $page) {
                // check page's language
                if ($language != $page['language']) {
                    $language = $page['language'];
                    $levels   = [];
                }

                $levels[$page->level] = $page->slug;
                $pagesMap[$page->language][$page->slug] = [
                    'id' => $page->id,
                    'title'  => $page->title,
                    'slug' => $page->slug,
                    'system_title'  => $page->system_title,
                    'active' => $page->active,
                    'module_status' => $page->module_status,
                    'level' => $page->level,
                    'privacy' => $page->privacy,
                    'pages_provider' => $page->pages_provider,
                    'parent' => (isset($levels[$page->level - 1]) ? $levels[$page->level - 1] : null),
                    'site_map' => $page->site_map,
                    'xml_map' => $page->xml_map,
                    'menu' => $page->menu,
                    'type' => $page->type,
                    'date_edited' => $page->date_edited,
                    'xml_map_update' => $page->xml_map_update,
                    'xml_map_priority' => $page->xml_map_priority,
                    'hidden' => isset($pagesVisibility[$page->id]) ? $pagesVisibility[$page->id] : []
                ];
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $pagesMap);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        self::$pagesMap = $pagesMap;
        return $pagesMap;
    }
}