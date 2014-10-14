<?php
namespace Application\Model;

use Zend\Db\Adapter\AdapterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Cache\Storage\StorageInterface;

class ApplicationModelManager
{
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
    private $instances = [];

    /**
     * Class constructor
     *
     * @param object $adapter
     * @param object $cache
     */
    public function __construct(AdapterInterface $adapter, StorageInterface $cache)
    {
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

        $this->instances[$modelName] = new $modelName($this->adapter, $this->cache);
        return $this->instances[$modelName];
    }
}
