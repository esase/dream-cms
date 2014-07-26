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
                $content .= $this->getView()->partial($injection['patrial']);
            }
        }

        return $content;
    }
}
