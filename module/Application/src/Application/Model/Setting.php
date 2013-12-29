<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtilities;

class Setting extends Base
{
    /**
     * List of settings
     * @var array
     */
    protected static $settings;

    /**
     * Array fields
     * @var array
     */
    protected $arrayFields = array('multiselect', 'multicheckbox');

    /**
     * Cache settings by language
     */
    const CACHE_SETTINGS_BY_LANGUAGE = 'Application_Settings_By_Language_';

    /**
     * Cache application settings tag
     */
    const CACHE_TAG_SETTINGS = 'Tag_Application_Settings';

    /**
     * System settings flag
     */
    const SYS_SETTINGS_FLAG = 'system';

    /**
     * Settings array devider
     */
    const SETTINGS_ARRAY_DEVIDER = ';';

    /**
     * Remove settings cache
     *
     * @param string $language
     * @return void
     */
    public function removeSettingsCache($language)
    {
        $this->staticCacheInstance->clearByTags(array(
            self::CACHE_TAG_SETTINGS
        ));
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
        return CacheUtilities::getCacheName(self::CACHE_SETTINGS_BY_LANGUAGE . $language);
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
            $subQuery->from(array('c' => 'settings_values'))
                ->columns(array(
                    'id'
                ))
                ->order('c.language desc')
                ->limit(1)
                ->where(array('a.id' => new Expression('c.setting_id')))
                ->where
                    ->and->isNull('c.language')
                ->where
                    ->or->equalTo('a.id', new Expression('c.setting_id'))
                    ->and->equalTo('c.language', $language);
    
            $mainSelect = $this->select();
            $mainSelect->from(array('a' => 'settings'))
                ->columns(array(
                    'name',
                    'type'
                ))
                ->join(
                    array('b' => 'settings_values'),
                    new Expression('b.id = (' .$this->getSqlStringForSqlObject($subQuery) . ')'),
                    array(
                        'value'
                    ),
                    'left'
                );

            $statement = $this->prepareStatementForSqlObject($mainSelect);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // convert strings
            $settings = array();
            foreach ($resultSet as $setting) {
                $settings[$setting['name']] = $this->convertString($setting['type'], $setting['value']);
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $settings);
            $this->staticCacheInstance->setTags($cacheName, array(
                self::CACHE_TAG_SETTINGS
            ));
        }

        return $settings;
    }

    /**
     * Convert string
     *
     * @param string $type
     * @param string $value
     * @return string|array
     */
    protected function convertString($type, $value)
    {
        if (in_array($type, $this->arrayFields)) {
            $value = explode(self::SETTINGS_ARRAY_DEVIDER, $value);
            return count($value) == 1 // check is array or not
                ? current($value)
                : $value;
        }

        return $value;
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