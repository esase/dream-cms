<?php

namespace Page\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class InjectWidget extends AbstractHelper
{
    /**
     * Widgets
     * @var array
     */
    protected $widgets = [];

    /**
     * Class constructor
     *
     * @param array $menu
     */
    public function __construct(array $widgets = [])
    {
        $this->widgets = $widgets;
        print_r($this->widgets);
    }

    /**
     * Inject widget
     *
     * @param string $position
     * @param integer $pageId
     * @return text
     */
    public function __invoke($position, $pageId = 0)
    {
        return $position . ' - ' . $pageId;
    }
}