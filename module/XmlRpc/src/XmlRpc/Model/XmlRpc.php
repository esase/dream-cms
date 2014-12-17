<?php
namespace XmlRpc\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\ApplicationCache as CacheUtility;

class XmlRpc extends XmlRpcBase
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
            $select->from(['a' =>'xmlrpc_class'])
                ->columns([
                    'namespace',
                    'path',
                    'module'
                ])
                ->join(
                    ['b' => 'application_module'],
                    new Expression('a.module = b.id and b.status = ?', [self::MODULE_STATUS_ACTIVE]),
                    []
                );

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $classes = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $classes);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_XMLRPC_DATA_TAG]);
        }

        return $classes;
    }
}