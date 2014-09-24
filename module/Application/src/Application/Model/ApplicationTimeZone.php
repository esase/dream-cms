<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\ApplicationCache as CacheUtility;

class ApplicationTimeZone extends ApplicationBase
{
    /**
     * Time zones cache
     */
    const CACHE_TIME_ZONES  = 'Application_Time_Zones';

    /**
     * Time zones data cache tag
     */
    const CACHE_TIME_ZONES_DATA_TAG = 'Application_Time_Zones_Data_Tag';

    /**
     * Time zones
     * @var array
     */
    protected static $timeZones = null;

    /**
     * Get time zones
     *
     * @return array
     */
    public function getTimeZones()
    {
        // check data in a memory
        if (null !== self::$timeZones) {
            return self::$timeZones;
        }

        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_TIME_ZONES);

        // check data in cache
        if (null === ($timeZones = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_time_zone')
                ->columns([
                    'id',
                    'name',
                ])
                ->order('name');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            foreach ($resultSet as $timeZone) {
                $timeZones[$timeZone->id] = $timeZone->name;
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $timeZones);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_TIME_ZONES_DATA_TAG]);
        }

        self::$timeZones = $timeZones;
        return $timeZones;
    }
}