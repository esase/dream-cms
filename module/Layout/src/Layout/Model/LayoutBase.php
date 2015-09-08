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
namespace Layout\Model;

use Application\Utility\ApplicationCache as CacheUtility;
use Application\Service\ApplicationSetting as SettingService;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\ResultSet\ResultSet;

class LayoutBase extends ApplicationAbstractBase
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
    const CACHE_LAYOUTS_BY_ID = 'Layout_List_By_Id_';
 
    /**
     * Active layouts cache
     */
    const CACHE_LAYOUTS_ACTIVE = 'Layout_List_Active';

    /**
     * Layouts data cache tag
     */
    const CACHE_LAYOUTS_DATA_TAG = 'Layout_Data_Tag';

    /**
     * Get all installed layouts
     *
     * @param boolean $onlyCustom
     * @return array
     */
    public function getAllInstalledLayouts($onlyCustom = false)
    {
        $select = $this->select();
        $select->from('layout_list')
            ->columns([
                'id',
                'name'
            ])
        ->order('id');

        if ($onlyCustom) {
            $select->where([
                'type' => self::LAYOUT_TYPE_CUSTOM
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $layoutsList = [];
        foreach ($resultSet as $layout) {
            $layoutsList[$layout->id] = $layout->name;
        }

        return $layoutsList;
    }

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
            $select->from('layout_list')
                ->columns([
                    'name',
                ])
                ->order('type')
                ->where([
                    'id' => $layoutId
                ])
                ->where->or->equalTo('type', self::LAYOUT_TYPE_SYSTEM);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $layouts);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_LAYOUTS_DATA_TAG]);
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
            $defaultActiveCustomLayout = (int) SettingService::getSetting('layout_active');

            $select = $this->select();
            $select->from('layout_list')
                ->columns([
                    'name'
                ])
                ->order('type')
                ->where([
                    'type' => self::LAYOUT_TYPE_SYSTEM
                ]);

            if ($defaultActiveCustomLayout) {
                $select->where->or->
                        equalTo('id', $defaultActiveCustomLayout)->and->equalTo('type', self::LAYOUT_TYPE_CUSTOM);
            }

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $layouts = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $layouts);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_LAYOUTS_DATA_TAG]);
        }

        return $layouts;
    }

    /**
     * Get layout info
     *
     * @param string $name
     * @return array
     */
    public function getLayoutInfo($name)
    {
        $select = $this->select();
        $select->from('layout_list')
            ->columns([
                'id',
                'name',
                'type',
                'version',
                'vendor',
                'vendor_email'
            ])
            ->where([
                'name' => $name
            ]);

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}