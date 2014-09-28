<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\ApplicationCache as CacheUtility;

class ApplicationSetting extends ApplicationAbstractSetting
{
    /**
     * List of settings
     * @var array
     */
    protected static $settings;

    /**
     * Application settings data cache
     */
    const CACHE_SETTINGS_BY_LANGUAGE = 'Application_Settings_By_Language_';

    /**
     * Application settings data cache tag
     */
    const CACHE_SETTINGS_DATA_TAG = 'Application_Settings_Data_Tag';

    /**
     * Remove settings cache
     *
     * @param string $language
     * @return void
     */
    public function removeSettingsCache($language)
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