<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtilities;

class Setting extends Base
{
    /**
     * Current list of settings
     * @var array
     */
    protected static $settings;

    /**
     * Cache settings by language
     */
    const CACHE_SETTINGS_BY_LANGUAGE = 'Application_Settings_By_Language_';

    /**
     * Cache application settings tag
     */
    const CACHE_TAG_SETTINGS = 'Tag_Application_Settings';

    /**
     * Get all settings
     *
     * @param string $language
     * @return array
     */
    protected function getAllSettings($language)
    {
        // generate cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_SETTINGS_BY_LANGUAGE . $language);

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
                    'name'
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

            $settings = array();
            foreach ($resultSet as $setting) {
                $settings[$setting['name']] = $setting['value'];
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
     * Get setting value
     *
     * @param string $settingName
     * @param string $language
     * @return string|array|boolean
     */
    public function getSetting($settingName, $language = null)
    {
        if (!self::$settings) {
            self::$settings = $this->getAllSettings($language);
        }

        if (isset(self::$settings[$settingName])) {
            $setting = explode(';', self::$settings[$settingName]);

            return count($setting) == 1
                ? current($setting)
                : $setting;
        }

        return false;
    }
}