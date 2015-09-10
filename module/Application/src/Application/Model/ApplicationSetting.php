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
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class ApplicationSetting extends ApplicationAbstractSetting
{
    /**
     * Application settings data cache
     */
    const CACHE_SETTINGS_BY_LANGUAGE = 'Application_Settings_By_Language_';

    /**
     * Application settings data cache tag
     */
    const CACHE_SETTINGS_DATA_TAG = 'Application_Settings_Data_Tag';

    /**
     * List of settings
     *
     * @var array
     */
    protected static $settings;

    /**
     * Remove settings cache
     *
     * @return void
     */
    public function removeSettingsCache()
    {
        $this->staticCacheInstance->clearByTags([
            self::CACHE_SETTINGS_DATA_TAG
        ]);
    }

    /**
     * Get settings cache name
     *
     * @param string $language
     * @return string
     */
    protected function getSettingsCacheName($language)
    {
        // generate cache name
        return CacheUtility::getCacheName(self::CACHE_SETTINGS_BY_LANGUAGE . $language);
    }

    /**
     * Get all settings
     *
     * @param string $language
     * @return array
     */
    protected function getAllSettings($language)
    {
        // get cache name
        $cacheName = $this->getSettingsCacheName($language);

        // check data in cache
        if (null === ($settings = $this->staticCacheInstance->getItem($cacheName))) {
            $subQuery= $this->select();
            $subQuery->from(['c' => 'application_setting_value'])
                ->columns([
                    'id'
                ])
                ->limit(1)
                ->where(['a.id' => new Expression('c.setting_id')])
                ->where
                    ->and->equalTo('c.language', $language)
                ->where
                    ->or->equalTo('a.id', new Expression('c.setting_id'))
                    ->and->isNull('c.language');
    
            $mainSelect = $this->select();
            $mainSelect->from(['a' => 'application_setting'])
                ->columns([
                    'name',
                    'type'
                ])
                ->join(
                    ['b' => 'application_setting_value'],
                    new Expression('b.id = (' .$this->getSqlStringForSqlObject($subQuery) . ')'),
                    ['value'],
                    'left'
                );

            $statement = $this->prepareStatementForSqlObject($mainSelect);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // convert strings
            $settings = [];
            foreach ($resultSet as $setting) {
                $settings[$setting['name']] = $this->convertString($setting['type'], $setting['value']);
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $settings);
            $this->staticCacheInstance->setTags($cacheName, [
                self::CACHE_SETTINGS_DATA_TAG
            ]);
        }

        return $settings;
    }

    /**
     * Get setting value
     *
     * @param string $settingName
     * @param string $language
     * @return string|array|boolean
     */
    public function getSetting($settingName, $language)
    {
        if (empty(self::$settings[$language])) {
            self::$settings[$language] = $this->getAllSettings($language);
        }

        if (isset(self::$settings[$language][$settingName])) {
            return self::$settings[$language][$settingName];
        }

        return false;
    }
}