<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;

class Injection extends Base
{
    /**
     * Injection cache name
     */
    const CACHE_INJECTION = 'Application_Injection';

    /**
     * Get injections
     *
     * @return array
     */
    public function getInjections()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_INJECTION);

        // check data in cache
        if (null === ($injections = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(array('a' => 'application_injection_connection'))
                ->columns(array(
                    'design_box'
                ))
            ->join(
                array('b' => 'application_injection_widget'),
                'a.widget_id = b.id',
                array(
                    'widget_name' => 'name',
                    'widget_title' => 'title',
                )
            )
            ->join(
                array('c' => 'application_injection_position'),
                'a.position_id = c.id',
                array(
                    'widget_position' => 'name'
                )
            )
            ->order('a.order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            foreach ($resultSet as $injection) {
                $injections[$injection->widget_position][] = array(
                    'design_box' => $injection->design_box,
                    'widget_name' => $injection->widget_name,
                    'widget_title' => $injection->widget_title,
                    'widget_title' => $injection->widget_title,
                );
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $injections);
        }

        return !$injections ? array() : $injections;
    }
}