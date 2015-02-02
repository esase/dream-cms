<?php
namespace Layout\Model;

use Application\Model\ApplicationAbstractBase;
use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;
use Application\Service\ApplicationSetting as SettingService;

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
                ->where
                    ->or->equalTo('type', self::LAYOUT_TYPE_SYSTEM);

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
                $select->where->
                    or->equalTo('id', $defaultActiveCustomLayout)->
                    and->equalTo('type', self::LAYOUT_TYPE_CUSTOM);
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