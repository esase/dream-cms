<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Utility\RouteParam as RouteParamUtility;

class ApplicationRouteParam extends AbstractHelper
{
    /**
     * Get route param
     *
     * @param string $param
     * @param string $defaultValue
     * @return string
     */
    public function __invoke($param, $defaultValue = null)
    {
        return RouteParamUtility::getParam($param, $defaultValue);
    }
}