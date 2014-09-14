<?php
namespace Page\Model;

use Application\Model\ApplicationAbstractBase;
use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;
use Zend\Db\Sql\Expression as Expression;

class PageBase extends ApplicationAbstractBase
{
    /**
     * Cache widgets connections
     */
    const CACHE_WIDGETS_CONNECTIONS = 'Page_Widgets_Connections_';

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
                    'a.user_menu' => Page::PAGE_IN_USER_MENU,
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
                    'a.footer_menu' => Page::PAGE_IN_FOOTER_MENU,
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
    protected function getAllPagesMap()
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
                    'menu',
                    'type',
                    'language'
                ])
                ->join(
                    ['b' => 'page_system'],
                    'b.id = a.system_page',
                    [
                        'privacy',
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
                    'title'  => $page->title,
                    'system_title'  => $page->system_title,
                    'active' => $page->active,
                    'level' => $page->level,
                    'privacy' => $page->privacy,
                    'parent' => (isset($levels[$page->level - 1]) ? $levels[$page->level - 1] : null),
                    'site_map' => $page->site_map,
                    'menu' => $page->menu,
                    'type' => $page->type,
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