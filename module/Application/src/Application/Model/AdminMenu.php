<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;
use Exception;
use Zend\Db\Sql\Expression as Expression;

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
        $cacheName = CacheUtility::getCacheName(self::CACHE_ADMIN_MENU);

        // check data in cache
        if (null === ($layouts = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(array('a' => 'admin_menu'))
                ->columns(array(
                    'name',
                    'controller',
                    'action'
                    
                ))
            ->join(
                array('b' => 'module'),
                new Expression('a.module = b.id and b.active = ' . (int) self::MODULE_ACTIVE),
                array(
                    'module' => 'id'
                )
            )
            ->join(
                array('c' => 'admin_menu_category'),
                'a.category = c.id',
                array(
                    'category' => 'name'
                )
            )
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