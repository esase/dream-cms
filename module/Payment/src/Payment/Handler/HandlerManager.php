<?php
namespace Payment\Handler;

use Zend\ServiceManager\ServiceLocatorInterface;
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;
use Payment\Exception\PaymentException;

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
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * Get an object instance
     *
     * @papam string $name
     * @throws Payment\Exception\PaymentException
     * @return object|boolean
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
            throw new PaymentException(sprintf('The file "%s" must be an object implementing Payment\Handler\InterfaceHandler', $name));
        }

        $this->instances[$name] = $handler;
        return $this->instances[$name];
    }
}