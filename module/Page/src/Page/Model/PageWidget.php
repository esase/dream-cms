<?php
namespace Page\Model;

use Zend\Db\Sql\Expression as Expression;
use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;

class PageWidget extends PageBase
{
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

            // get widgets connections
            $select = $this->select();
            $select->from(['a' => 'page_widget_connection'])
                ->columns([
                    'widget_connection_id' => 'id',
                    'widget_id'                    
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
                        'widget_name' => 'name'
                    ]
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
                )
                ->order('order')
                ->where->IsNull('a.page_id')
                    ->or->where->IsNotNull('a.page_id')
                    ->and->where->IsNotNull('b.id');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            $widgetConnections = [];
            foreach ($resultSet as $connection) {
                $widgetConnections[$connection->page_id][$connection->widget_position][] = [
                    'widget_name' => $connection->widget_name,
                    'widget_layout' => $connection->widget_layout,
                    'widget_connection_id' => $connection->widget_connection_id,
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
}