<?php
namespace Page\Model;

use Application\Model\AbstractBase;
use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;
use Zend\Db\Sql\Expression as Expression;

class Base extends AbstractBase
{
    /**
     * Cache widgets connections
     */
    const CACHE_WIDGETS_CONNECTIONS = 'Page_Widgets_Connections_';

    /**
     * Cache pages map
     */
    const CACHE_PAGES_MAP = 'Page_Pages_Map_';

    /**
     * Cache pages tree
     */
    const CACHE_PAGES_TREE = 'Page_Pages_Tree_';

    /**
     * Pages data cache tag
     */
    const CACHE_PAGES_DATA_TAG = 'Page_Data_Tag';

    /**
     * Pages map
     * @var array
     */
    protected static $pagesMap = [];

    /**
     * Pages tree
     * @var array
     */
    protected static $pagesTree = [];

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
    public function getPagesMap($language)
    {
        if (isset(self::$pagesMap[$language])) {
            return self::$pagesMap[$language];
        }

        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_MAP . $language);

        // check data in a cache
        if (null === ($pagesMap = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('page_structure')
                ->columns([
                    'slug',
                    'title',
                    'level',
                    'active',
                    'privacy',
                    'redirect_url',
                    'site_map',
                    'disable_site_map',
                    'menu',
                    'disable_menu',
                ])
                ->where([
                    'language' => $language
                ])
                ->order('left_key');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $levels = $pagesMap = [];
            foreach ($resultSet as $pathInfo) {
                $levels[$pathInfo->level] = $pathInfo->slug;
                $pagesMap[$pathInfo->slug] = [
                    'title'  => $pathInfo->title,
                    'active' => $pathInfo->active,
                    'level' => $pathInfo->level,
                    'privacy' => $pathInfo->privacy,
                    'parent' => (isset($levels[$pathInfo->level - 1]) ? $levels[$pathInfo->level - 1] : null),
                    'redirect_url' => $pathInfo->redirect_url,
                    'site_map' => $pathInfo->site_map,
                    'disable_site_map' => $pathInfo->disable_site_map,
                    'menu' => $pathInfo->menu,
                    'disable_menu' => $pathInfo->disable_menu
                ];
            }

            // get pages visibility
            $select = $this->select();
            $select->from(['a' => 'page_visibility'])
                ->columns([
                    'hidden',
                ])
                ->join(
                    ['b' => 'page_structure'],
                    new Expression('b.id = a.page_id and b.language = ?', [$language]),
                    [
                        'slug'
                    ]
                );

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            foreach ($resultSet as $pageVisibility) {
                if (!empty($pagesMap[$pageVisibility->slug])) {
                    $pagesMap[$pageVisibility->slug]['hidden'][] = $pageVisibility->hidden;
                }
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $pagesMap);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_PAGES_DATA_TAG]);
        }

        self::$pagesMap[$language] = $pagesMap;
        return $pagesMap;
    }
}