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
     * @return array|boolean
     */
    public function getAllPageStructureChildren($pageId)
    {
        return $this->getPageModel()->getAllPageChildren($pageId);
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
     * Get page layouts
     *
     * @return array
     */
    public function getPageLayouts()
    {
        $select = $this->select();
        $select->from('page_layout')
            ->columns([
                'id',
                'title',
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $layouts = [];
        foreach ($resultSet as $layout) {
            $layouts[$layout->id] = $layout->title;
        }

        return $layouts;
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
        $select->from('page_system')
            ->columns([
                'id'
            ])
            ->where(['slug' => $slug]);

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
     * Clear widgets connections cache
     *
     * @return boolean
     */
    public function clearWidgetsConnectionsCache()
    {
        $cacheName = CacheUtility::
                getCacheName(self::CACHE_WIDGETS_CONNECTIONS . $this->getCurrentLanguage());

        // clear a page's widgets settings cache
        if ($this->staticCacheInstance->hasItem($cacheName)) {
            return $this->staticCacheInstance->removeItem($cacheName);
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
                    'layout_default_position' => 'default_position'
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
        // TODO: CLEAR THE 'CACHE_USER_MENU' AFTER ANY CHANGES IN PAGES STRUCTURE FOR A SELECTED LANGUAGE

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
        // TODO: CLEAR THE 'CACHE_FOOTER_MENU' AFTER ANY CHANGES IN PAGES STRUCTURE FOR A SELECTED LANGUAGE

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
        // TODO: CLEAR THE 'CACHE_PAGES_TREE' AFTER ANY CHANGES IN PAGES STRUCTURE FOR A SELECTED LANGUAGE

        if (isset(self::$pagesTree[$language])) {
            return self::$pagesTree[$language];
        }

        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_TREE . $language);

        // check data in a cache
        if (null === ($pagesTree = $this->staticCacheInstance->getItem($cacheName))) {
            $pagesTree = [];

            // process pages map
            foreach ($this->getPagesMap($language) as $pageName => $pageOptions) {
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
        // TODO: CLEAR THE 'CACHE_PAGES_MAP' AFTER ANY CHANGES IN PAGES STRUCTURE FOR ALL LANGUAGES

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