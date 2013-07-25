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

        return new $modelName($this->serviceManager->
                get('Zend\Db\Adapter\Adapter'), $this->serviceManager->get('Custom\Cache\Static\Utils'));
    }
}
