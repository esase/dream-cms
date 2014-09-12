<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Utility\RouteParam as RouteParamUtility;

class ApplicationRoute extends AbstractHelper
{
    /**
     * Application route
     *
     * @return object - fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get route param
     *
     * @param string $param
     * @param string $defaultValue
     * @return string
     */
    public function getParam($param, $defaultValue = null)
    {
        return RouteParamUtility::getParam($param, $defaultValue);
    }

    /**
     * Get query
     *
     * @return array
     */
    public function getQuery()
    {
        return RouteParamUtility::getQuery();
    }
}