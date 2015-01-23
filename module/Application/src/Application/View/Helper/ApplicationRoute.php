<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Utility\ApplicationRouteParam as RouteParamUtility;

class ApplicationRoute extends AbstractHelper
{
    /**
     * Query
     * @var array
     */
    protected $query = null;

    /**
     * All route default params
     * @var array
     */
    protected $allRouteDefaultParams = [
        'language',
        'page_name',
        'page',
        'per_page',
        'order_by',
        'category',
        'date',
        'slug'
    ];

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
     * Get all default route params
     *
     * @return array
     */
    public function getAllDefaultRouteParams()
    {
        $params = [];

        foreach ($this->allRouteDefaultParams as $param) {
            if (null !== ($value = $this->getParam($param, null))) {
                $params[$param] = $value;
            }
        }

        return $params;
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

    /**
     * Get query param
     *
     * @return mixed
     */
    public function getQueryParam($param, $defaultValue = null)
    {
        if (null === $this->query) {
            $this->query = $this->getQuery();
        }

        return !empty($this->query[$param]) ? $this->query[$param] : $defaultValue;
    }
}