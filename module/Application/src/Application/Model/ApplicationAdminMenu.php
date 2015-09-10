<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Model;

use Application\Utility\ApplicationCache as CacheUtility;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\ResultSet\ResultSet;

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
                            [
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