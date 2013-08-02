<?php

namespace Application\Model;

class ModelBuilder implements ModelBuilderInterface
{
    /**
     * Service manager
     * @var object
     */
    private $serviceManager;

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
    public function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
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

        $this->instances[$modelName] = new $modelName($this->
                serviceManager->get('Zend\Db\Adapter\Adapter'), $this->serviceManager->get('Custom\Cache\Static\Utils'));

        return $this->instances[$modelName];
    }
}
