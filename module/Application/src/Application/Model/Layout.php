<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;

class Layout extends Base
{
    /**
     * System layout flag
     */
    const LAYOUT_TYPE_SYSTEM = 'system';

    /**
     * Custom layout flag
     */
    const LAYOUT_TYPE_CUSTOM = 'custom';

    /**
     * Layouts by name
     */
    const CACHE_LAYOUTS_BY_NAME = 'Application_Layouts_BY_NAME_';
 
    /**
     * Active layouts cache
     */
    const CACHE_LAYOUTS_ACTIVE = 'Application_Layouts_Active';

    /**
     * Layouts cache tag
     */
    const CACHE_LAYOUTS_TAG = 'Application_Layouts_TAG';

    /**
     * Get layouts by name
     *
     * @param string $layoutName
     * @return array
     */
    public function getLayoutsByName($layoutName)
    {
        // generate cache name
        $cacheName = $this->staticCacheUtils->
                getCacheName(self::CACHE_LAYOUTS_BY_NAME . $layoutName);

        // check data in cache
        if (null === ($layouts = $this->
                staticCacheUtils->getCacheInstance()->getItem($cacheName))) {

            $select = $this->select();
            $select->from('layouts')
                ->columns(array(
                    'name',
                ))
                ->order('type')
                ->where(array(
                    'name' => $layoutName
                ))
                ->where
                    ->or->equalTo('type', self::LAYOUT_TYPE_SYSTEM)
                    ->and->equalTo('active', 1);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheUtils->getCacheInstance()->setItem($cacheName, $layouts);

            // mark cache with tag
            $this->staticCacheUtils->getCacheInstance()->setTags($cacheName, array(
                self::CACHE_LAYOUTS_TAG
            ));
        }

        return $layouts;
    }

    /**
     * Get default active layouts
     *
     * @return array
     */
    public function getDefaultActiveLayouts()
    {
        // generate cache name
        $cacheName = $this->staticCacheUtils->getCacheName(self::CACHE_LAYOUTS_ACTIVE);

        // check data in cache
        if (null === ($layouts = $this->
                staticCacheUtils->getCacheInstance()->getItem($cacheName))) {

            $select = $this->select();
            $select->from('layouts')
                ->columns(array(
                    'name'
                ))
                ->order('type')
                ->where(array(
                    'type' => self::LAYOUT_TYPE_SYSTEM
                ))
                ->where
                    ->or->equalTo('type', self::LAYOUT_TYPE_CUSTOM)
                    ->and->equalTo('active', 1);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheUtils->getCacheInstance()->setItem($cacheName, $layouts);

            // mark cache with tag
            $this->staticCacheUtils->getCacheInstance()->setTags($cacheName, array(
                self::CACHE_LAYOUTS_TAG
            ));
        }

        return $layouts;
    }
}