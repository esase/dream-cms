<?php
namespace Page\Utility;

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