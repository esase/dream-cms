<?php

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Application\Utility\Slug as SlugUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Application\Utility\Cache as CacheUtility;
use Exception;
use Zend\ServiceManager\ServiceManager;

abstract class AbstractBase extends Sql
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

    /**
     * Slug salt length
     * @var integer
     */
    protected $slugSaltLength = 10;

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
    public function __construct(Adapter $adapter, CacheAdapter $staticCacheInstance, ServiceManager $serviceManager)
    {
        parent::__construct($adapter);

        $this->serviceManager = $serviceManager;
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
    public function generateSlug($objectId, $title, $table, $idField, $slugLength = 60, $slugField = 'slug', $spaceDevider = '-')
    {
        // generate a slug
        $newSlug  = $slug = SlugUtility::slugify($title, $slugLength, $spaceDevider);
        $slagSalt = null;

        while (true) {
            // check the slug existent
            $select = $this->select();
            $select->from($table)
                ->columns(array(
                    $slugField
                ))
                ->where(array(
                    $slugField => $newSlug,
                    new NotInPredicate($idField, array($objectId))
                ));
    
            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // generated slug not found
            if (!$resultSet->current()) {
                break;
            }
            else {
                $newSlug = $objectId . $spaceDevider . $slug . $slagSalt;
            }

            // add an extra slug
            $slagSalt = $spaceDevider . SlugUtility::generateRandomSlug($this->slugSaltLength); // add a salt
        }

        return $newSlug;
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
        $cacheName = CacheUtility::getCacheName(self::CACHE_MODULE_BY_NAME . $moduleName);

        // check data in cache
        if (null === ($module = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('module')
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
        $select->from('module')
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
        return SlugUtility::generateRandomSlug($length, $charlist);
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