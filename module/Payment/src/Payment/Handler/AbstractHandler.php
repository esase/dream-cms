<?php
namespace Payment\Handler;

use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractHandler implements InterfaceHandler
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Class constructor
     *
     * @param object $serviceManager
     */
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }
}