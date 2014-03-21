<?php

namespace XmlRpc\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtility;

class XmlRpc extends Base
{
    /**
     * Get all classes
     *
     * @return array
     */
    public function getClasses()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_XMLRPC_CLASSES);

        // check data in cache
        if (null === ($classes = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('xmlrpc_class')
                ->columns(array(
                    'namespace',
                    'path',
                    'module'
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $classes = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $classes);
        }

        return $classes;
    }
}