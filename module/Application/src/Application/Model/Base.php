<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtilities;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;

class Base extends Sql
{
    /**
     * Module by name
     */
    const CACHE_MODULE_BY_NAME = 'Application_Module_By_Name_';
 
    /**
     * Module action flag
     */
    const MODULE_ACTIVE = 1;

    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

    /**
     * Class constructor
     *
     * @param object $adapter
     * @param object $staticCacheInstance
     */
    public function __construct(Adapter $adapter, CacheAdapter $staticCacheInstance)
    {
        parent::__construct($adapter);
        $this->staticCacheInstance = $staticCacheInstance;
    }

    /**
     * Get module info
     *
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo($moduleName)
    {
        // generate cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_MODULE_BY_NAME . $moduleName);

        // check data in cache
        if (null === ($module = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('modules')
                ->columns(array(
                    'id',
                    'name',
                    'type',
                    'active',
                    'version',
                    'vendor',
                    'vendor_email',
                    'description',
                    'dependences'
                ))
                ->where(array('name' => $moduleName));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $module = $resultSet->current();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $module);
        }

        return $module;
    }

    /**
     * Get active modules list
     *
     * @return array
     */
    public function getActiveModulesList()
    {
        $modulesList = array();

        $select = $this->select();
        $select->from('modules')
            ->columns(array(
                'id',
                'name'
            ))
        ->where(array(
            'active' => self::MODULE_ACTIVE
        ))
        ->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        foreach ($resultSet as $module) {
            $modulesList[$module->id] = $module->name;
        }

        return $modulesList;
    }

    /**
     * Get adapter
     *
     * @return object
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}