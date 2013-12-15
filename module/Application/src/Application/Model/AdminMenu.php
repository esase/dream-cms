<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtilities;

class AdminMenu extends Base
{
    /**
     * Admin menu
     */
    const CACHE_ADMIN_MENU = 'Application_Admin_Menu';

    /**
     * Get menu
     *
     * @return array
     */
    public function getMenu()
    {
        // generate cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_ADMIN_MENU);

        // check data in cache
        if (null === ($layouts = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('admin_menu')
                ->columns(array(
                    'name',
                    'controller',
                    'action'
                    
                ))
            ->order('order');

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