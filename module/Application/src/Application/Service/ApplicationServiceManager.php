<?php
namespace Application\Service;

use Zend\ServiceManager\ServiceLocatorInterface;

class ApplicationServiceManager
{
    /**
     * Service manager
     */
    protected static $serviceManager;

    /**
     * Set service manager
     *
     * @param object $serviceManager
     * @return void
     */
    public static function setServiceManager(ServiceLocatorInterface $serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }

    /**
     * Get service manager
     *
     * @return object
     */
    public static function getServiceManager()
    {
        return self::$serviceManager;
    }
}