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
     * Pages data cache tag
     */
    const CACHE_PAGES_DATA_TAG = 'Page_Data_Tag';

    /**
     * Get pages map
     * 
     * @param string $language
     * @return array
     */
    public function getPagesMap($language)
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_PAGES_MAP . $language);

        // check data in cache
        if (null === ($pagesMap = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('page_structure')
                ->columns([
                    'slug',
                    'title',
                    'level',
                    'active',
                    'privacy'
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
                    'parent' => (isset($levels[$pathInfo->level - 1]) ? $levels[$pathInfo->level - 1] : null)
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

        return $pagesMap;
    }
}