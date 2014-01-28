<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Zend\Math\Rand;
use Application\Utility\Slug;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Application\Utility\Cache as CacheUtilities;

abstract class AbstractBase extends Sql
{
    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;
    
    /**
     * Module by name
     */
    const CACHE_MODULE_BY_NAME = 'Application_Module_By_Name_';

    /**
     * Module action flag
     */
    const MODULE_ACTIVE = 1;

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
     * Generate slug
     *
     * @param integer $objectId
     * @param string $title
     * @param string $table
     * @param string $idField
     * @param integer $slugLength 
     * @param string $slugField
     * @param string $spaceDevider
     * @return string
     */
    protected function generateSlug($objectId, $title, $table, $idField, $slugLength = 100, $slugField = 'slug', $spaceDevider = '-')
    {
        // generate a slug
        $slug = Slug::slugify($title, $slugLength, $spaceDevider);

        // check the slug existent
        $select = $this->select();
        $select->from($table)
            ->columns(array(
                $slugField
            ))
            ->where(array(
                $slugField => $slug,
                new NotInPredicate($idField, array($objectId))
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current()
            ? $objectId . $spaceDevider . $slug
            : $slug;
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
     * Generate a rand string
     *
     * @param integer $length
     * @param  string|null $charlist
     * @return string
     */
    public function generateRandString($length = 10, $charlist = null)
    {
        return Rand::getString($length, $charlist, true);
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