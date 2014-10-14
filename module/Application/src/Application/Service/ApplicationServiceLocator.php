<?php
namespace Application\Service;

use Zend\ServiceManager\ServiceLocatorInterface;

class ApplicationServiceLocator
{
    /**
     * Service locator
     * @var object
     */
    protected static $serviceLocator;

    /**
     * Set service locator
     *
     * @param object $serviceLocator
     * @return void
     */
    public static function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        self::$serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return object
     */
    public static function getServiceLocator()
    {
        return self::$serviceLocator;
    }
}