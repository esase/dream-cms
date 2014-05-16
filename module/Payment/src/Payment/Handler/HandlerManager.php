<?php

namespace Payment\Handler;

use Zend\Mvc\Exception\InvalidArgumentException;
use Zend\ServiceManager\ServiceManager;
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;

class HandlerManager
{
    /**
     * List of instances
     * @var array
     */
    private $instances = array();

    /**
     * Service manager
     * @var object
     */
    private $serviceManager;

    /**
     * Class constructor
     * 
     * @param object $translator
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * Get an object instance
     *
     * @papam string $name
     * @return object|boolean
     * @throws Exception\InvalidArgumentException
     */
    public function getInstance($name)
    {
        if (!class_exists($name)) {
            return false;
        }

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }

        $handler = new $name($this->serviceManager);

        if (!$handler instanceof PaymentInterfaceHandler) {
            throw new InvalidArgumentException(sprintf('The file "%s" must be an object implementing Payment\Handler\InterfaceHandler', $name));
        }

        $this->instances[$name] = $handler;
        return $this->instances[$name];
    }
}
