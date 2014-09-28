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
            $select->from(['a' => 'application_admin_menu'])
                ->columns([
                    'name',
                    'controller',
                    'action'
                    
                ])
            ->join(
                ['b' => 'application_admin_menu_category'],
                'a.category = b.id',
                [
                    'category' => 'name',
                    'category_icon' => 'icon'
                ]
            )
            ->join(
                ['c' => 'application_admin_menu_part'],
                'a.part = c.id',
                [
                    'part' => 'name',
                    'part_icon' => 'icon'
                ]
            )
            ->join(
                ['d' => 'application_module'],
                new Expression('c.module = d.id and d.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'part_module' => 'name'
                ]
            )
            ->join(
                ['i' => 'application_module'],
                new Expression('b.module = i.id and i.status = ?', [self::MODULE_STATUS_ACTIVE]),
                [
                    'category_module' => 'name'
                ]
            )
            ->order('order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // process admin menu
            foreach ($resultSet as $menuItem) {
                if (!isset($menu[$menuItem['part']])) {
                    $menu[$menuItem['part']] = [
                        'part' => $menuItem['part'],
                        'icon' => $menuItem['part_icon'],
                        'module' => $menuItem['part_module'],
                        'items' => [
                            0 => [
                                'name' => $menuItem['name'],
                                'controller' => $menuItem['controller'],
                                'action'  => $menuItem['action'],
                                'category' => $menuItem['category'],
                                'category_icon' => $menuItem['category_icon'],
                                'category_module' => $menuItem['category_module']
                            ]
                        ]
                    ];
                }
                else {
                    $menu[$menuItem['part']]['items'][] = [
                        'name' => $menuItem['name'],
                        'controller' => $menuItem['controller'],
                        'action'  => $menuItem['action'],
                        'category' => $menuItem['category'],
                        'category_icon' => $menuItem['category_icon'],
                        'category_module' => $menuItem['category_module']
                    ];
                }
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $menu);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_ADMIN_MENU_DATA_TAG]);
        }

        return $menu;
    }
}