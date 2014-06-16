<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;
use Exception;

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
     * Layouts by id
     */
    const CACHE_LAYOUTS_BY_ID = 'Application_Layouts_By_Id_';
 
    /**
     * Active layouts cache
     */
    const CACHE_LAYOUTS_ACTIVE = 'Application_Layouts_Active';

    /**
     * Active layout flag
     */ 
    const LAYOUT_STATUS_ACTIVE = 'active';

    /**
     * Not active layout flag
     */ 
    const LAYOUT_STATUS_NOT_ACTIVE = 'not_active';

    /**
     * Get layouts by id
     *
     * @param integer $layoutId
     * @return array
     */
    public function getLayoutsById($layoutId)
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_LAYOUTS_BY_ID . $layoutId);

        // check data in cache
        if (null === ($layouts = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('layout')
                ->columns(array(
                    'name',
                ))
                ->order('type')
                ->where(array(
                    'id' => $layoutId
                ))
                ->where
                    ->or->equalTo('type', self::LAYOUT_TYPE_SYSTEM)
                    ->and->equalTo('status', self::LAYOUT_STATUS_ACTIVE);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $layouts);
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
        $cacheName = CacheUtility::getCacheName(self::CACHE_LAYOUTS_ACTIVE);

        // check data in cache
        if (null === ($layouts = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('layout')
                ->columns(array(
                    'name'
                ))
                ->order('type')
                ->where(array(
                    'type' => self::LAYOUT_TYPE_SYSTEM
                ))
                ->where
                    ->or->equalTo('type', self::LAYOUT_TYPE_CUSTOM)
                    ->and->equalTo('status', self::LAYOUT_STATUS_ACTIVE);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $layouts);
        }

        return $layouts;
    }
}