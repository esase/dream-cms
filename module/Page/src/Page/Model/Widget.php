<?php
namespace Page\Model;

class Widget extends Base
{
    /**
     * Get widgets connections
     *
     * @return array
     */
    public function getWidgetsConnections()
    {
        // generate cache name
        /*$cacheName = CacheUtility::getCacheName(self::CACHE_WIDGETS_CONNECTIONS);

        // check data in cache
        if (null === ($widgets = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('page_widget_connection')
                ->columns(array(
                    'namespace',
                    'path',
                    'module'
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $widgets = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $widgets);
            $this->staticCacheInstance->setTags($cacheName, array(self::CACHE_PAGES_DATA_TAG));
        }

        return $widgets;*/
        return array();
    }
}