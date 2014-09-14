<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;
use Exception;
use Zend\Db\Sql\Expression as Expression;

class ApplicationAdminMenu extends ApplicationBase
{
    /**
     * Admin menu
     */
    const CACHE_ADMIN_MENU = 'Application_Admin_Menu';

    /**
     * Admin menu data cache tag
     */
    const CACHE_ADMIN_MENU_DATA_TAG = 'Application_Admin_Menu_Data_Tag';

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
        if (null === ($menu = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(array('a' => 'application_admin_menu'))
                ->columns(array(
                    'name',
                    'controller',
                    'action'
                    
                ))
            ->join(
                array('b' => 'application_admin_menu_category'),
                'a.category = b.id',
                array(
                    'category' => 'name',
                    'category_icon' => 'icon'
                )
            )
            ->join(
                array('c' => 'application_admin_menu_part'),
                'a.part = c.id',
                array(
                    'part' => 'name',
                    'part_icon' => 'icon'
                )
            )
            ->join(
                array('d' => 'application_module'),
                new Expression('c.module = d.id and d.status = ?', array(self::MODULE_STATUS_ACTIVE)),
                array(
                    'part_module' => 'name'
                )
            )
            ->join(
                array('i' => 'application_module'),
                new Expression('b.module = i.id and i.status = ?', array(self::MODULE_STATUS_ACTIVE)),
                array(
                    'category_module' => 'name'
                )
            )
            ->order('order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // process admin menu
            foreach ($resultSet as $menuItem) {
                if (!isset($menu[$menuItem['part']])) {
                    $menu[$menuItem['part']] = array(
                        'part' => $menuItem['part'],
                        'icon' => $menuItem['part_icon'],
                        'module' => $menuItem['part_module'],
                        'items' => array(
                            0 => array(
                                'name' => $menuItem['name'],
                                'controller' => $menuItem['controller'],
                                'action'  => $menuItem['action'],
                                'category' => $menuItem['category'],
                                'category_icon' => $menuItem['category_icon'],
                                'category_module' => $menuItem['category_module']
                            )
                        )
                    );
                }
                else {
                    $menu[$menuItem['part']]['items'][] = array(
                        'name' => $menuItem['name'],
                        'controller' => $menuItem['controller'],
                        'action'  => $menuItem['action'],
                        'category' => $menuItem['category'],
                        'category_icon' => $menuItem['category_icon'],
                        'category_module' => $menuItem['category_module']
                    );
                }
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $menu);
            $this->staticCacheInstance->setTags($cacheName, array(self::CACHE_ADMIN_MENU_DATA_TAG));
        }

        return $menu;
    }
}