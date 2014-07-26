<?php

namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class Injection extends AbstractHelper
{
    /**
     * Injections
     * @var array
     */
    protected $injections;

    /**
     * Class constructor
     *
     * @param array $injections
     *      string position
     *          string patrial
     *          string module
     */
    public function __construct(array $injections = array())
    {
        $this->injections = $injections;
    }

    /**
     * Injections
     *
     * @param string $position
     * @return string
     */
    public function __invoke($position)
    {
        $content = null;

        if (!empty($this->injections[$position])) {
            foreach ($this->injections[$position] as $injection) {
                $widget = $injection['widget_name'];
                $result = $this->getView()->$widget();

                // wrap content into the design box
                if ($injection['design_box']) {
                    $result = $this->getView()->partial('partial/panel', array(
                        'title' => $this->getView()->translate($injection['widget_title']),
                        'body' => $result
                    ));
                }

                $content .= $result;
            }
        }

        return $content;
    }
}