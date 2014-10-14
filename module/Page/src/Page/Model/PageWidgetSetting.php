<?php
namespace Page\Model;

use Application\Utility\ApplicationCache as CacheUtility;
use Application\Model\ApplicationAbstractSetting;
use Page\Model\PageBase as PageBaseModel;
use Zend\Db\ResultSet\ResultSet;

class PageWidgetSetting extends ApplicationAbstractSetting
{
    /**
     * List of settings
     * @var array
     */
    protected static $settings;

    /**
     * Get widget setting value
     *
     * @param integer $pageId
     * @param integer $connectionId
     * @param string $settingName
     * @return string|array|boolean
     */
    public function getWidgetSetting($pageId, $connectionId, $settingName)
    {
        // get all settings
        if (!isset(self::$settings[$pageId])) {
            self::$settings[$pageId] = $this->getAllSettings($pageId);
        }

        if (isset(self::$settings[$pageId][$connectionId][$settingName])) {
            return self::$settings[$pageId][$connectionId][$settingName];
        }

        return false;
    }

    /**
     * Get all settings
     *
     * @param integer $pageId
     * @return array
     */
    protected function getAllSettings($pageId)
    {
        // get cache name
        $cacheName = CacheUtility::getCacheName(PageBaseModel::CACHE_WIDGETS_SETTINGS_BY_PAGE . $pageId);

        // check data in cache
        if (null === ($settings = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(['a' => 'page_widget_connection'])
                ->columns([
                    'id'
                ])
                ->join(
                    ['b' => 'page_widget_setting'],
                    'a.widget_id = b.widget',
                    [
                        'name',
                        'type'
                    ]
                )
                ->join(
                    ['c' => 'page_widget_setting_value'],
                    'b.id = c.setting_id and a.id = c.widget_connection',
                    [
                        'value'
                    ]
                )
                ->where([
                    'page_id' => $pageId
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // convert strings
            $settings = [];
            foreach ($resultSet as $setting) {
                $settings[$setting['id']][$setting['name']] = $this->convertString($setting['type'], $setting['value']);
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $settings);
            $this->staticCacheInstance->setTags($cacheName, [PageBaseModel::CACHE_PAGES_DATA_TAG]);
        }

        return $settings;
    }
}