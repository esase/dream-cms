<?php
namespace Application\Utility;

use Application\Service\ServiceManager as ServiceManagerService;

class RouteParam
{
    /**
     * Route match
     * @var object
     */
    protected static $routeMatch;

    /**
     * Get a route param 
     *
     * @param string $paramName
     * @param string $defaultValue
     * @return string
     */
    public static function getParam($paramName, $defaultValue = null)
    {
        return self::getRouteMatch()->getParam($paramName, $defaultValue);
    }

    /**
     * Get a route query
     *
     * @return array
     */
    public static function getQuery()
    {
        return ServiceManagerService::getServiceManager()->get('Request')->getQuery()->toArray();
    }

    /**
     * Get route match
     *
     * @return object
     */
    protected static function getRouteMatch()
    {
        if (!self::$routeMatch) {
            self::$routeMatch = ServiceManagerService::
                    getServiceManager()->get('Application')->getMvcEvent()->getRouteMatch();
        }

        return self::$routeMatch;
    }
}