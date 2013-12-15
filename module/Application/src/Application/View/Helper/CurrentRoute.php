<?php

namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Stdlib\Parameters;

class CurrentRoute extends AbstractHelper
{
    /**
     * Route match
     * @var object
     */
    protected $routeMatch;

    /**
     * Query
     * @var array
     */
    protected $query;

    /**
     * Default route params
     * @var array
     */
    protected $defaultRouteParams = array(
        'controller',
        'action',
        'languge'
    );

    /**
     * Class constructor
     *
     * @param object $routeMatch
     */
    public function __construct(RouteMatch $routeMatch, Parameters $query = null)
    {
        $this->routeMatch = $routeMatch;
        $this->query = $query ? $query->toArray() : array();
    }

    /**
     * Current route
     *
     * @return object - fluent interface
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->routeMatch->getParam('controller');
    }

    /**
     * Get action name
     *
     * @return string
     */
    public function getAction()
    {
        return $this->routeMatch->getParam('action');
    }

    /**
     * Get language 
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->routeMatch->getParam('languge');
    }

    /**
     * Get extra route params
     *
     * @return array
     */
    public function getExtraRouteParams()
    {
        $params = array();
        foreach ($this->routeMatch->getParams() as $name => $value) {
            if (in_array($name, $this->defaultRouteParams)) {
                continue;    
            }

            $params[$name] = $value;
        }

        return $params;
    }

    /**
     * Get query
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }
}
