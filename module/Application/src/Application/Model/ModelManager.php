<?php

namespace Application\Model;

use Zend\Db\Adapter\AdapterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Cache\Storage\StorageInterface;

class ModelManager
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Adapter
     * @var object
     */
    private $adapter;

    /**
     * Cache
     * @var object
     */
    private $cache;

    /**
     * List of models instances
     * @var array
     */
    private $instances = array();

    /**
     * Class constructor
     * 
     * @param object $serviceManager
     */
    public function __construct(AdapterInterface $adapter, StorageInterface $cache, ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager =  $serviceManager;
        $this->adapter = $adapter;
        $this->cache = $cache;
    }

    /**
     * Get instance of specified model
     *
     * @papam string $modelName
     * @return object|boolean
     */
    public function getInstance($modelName)
    {
        if (!class_exists($modelName)) {
            return false;
        }

        if (array_key_exists($modelName, $this->instances)) {
            return $this->instances[$modelName];
        }

        $this->instances[$modelName] = new $modelName($this->adapter, $this->cache, $this->serviceManager);
        return $this->instances[$modelName];
    }
}
