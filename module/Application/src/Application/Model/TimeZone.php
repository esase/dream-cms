<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;

class TimeZone extends Base
{
    /**
     * Time zones cache
     */
    const CACHE_TIME_ZONES  = 'Application_Time_Zones';

    /**
     * Get time zones
     *
     * @return array
     */
    public function getTimeZones()
    {
        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_TIME_ZONES);

        // check data in cache
        if (null === ($timeZones = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('time_zone')
                ->columns(array(
                    'id',
                    'name',
                ))
                ->order('name');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            foreach ($resultSet as $timeZone) {
                $timeZones[$timeZone->id] = $timeZone->name;
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $timeZones);
        }

        return $timeZones;
    }
}