<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Application\Utility\Cache as CacheUtility;
use Exception;
use Zend\Db\Sql\Expression as Expression;

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
            $select->from('injection')
                ->columns(array(
                    'id',
                    'position',
                    'patrial',
                    'module',
                    'order'
                ))
            ->order('order');

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            foreach ($resultSet as $injection) {
                $injections[$injection->position][] = array(
                    'patrial' => $injection->patrial,
                    'module'  => $injection->module
                );
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $injections);
        }

        return !$injections ? array() : $injections;
    }
}