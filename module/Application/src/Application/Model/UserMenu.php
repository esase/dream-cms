<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;
use Zend\Db\Sql\Expression as Expression;

class UserMenu extends Base
{
    /**
     * User menu
     */
    const CACHE_USER_MENU = 'Application_User_Menu';

    /**
     * Get menu
     *
     * @return array
     */
    public function getMenu()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_USER_MENU);

        // check data in cache
        if (null === ($menu = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(array('a' => 'user_menu'))
                ->columns(array(
                    'name',
                    'controller',
                    'action',
                    'check'
                    
                ))
            ->join(
                array('b' => 'module'),
                new Expression('a.module = b.id and b.status = ?', array(self::MODULE_STATUS_ACTIVE)),
                array(
                    'module' => 'id'
                )
            )
            ->order('order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $menu = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $menu);
        }

        return $menu;
    }
}