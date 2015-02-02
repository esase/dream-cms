<?php
namespace Layout\View\Helper;

use Zend\View\Helper\AbstractHelper;

class LayoutList extends AbstractHelper
{
    /**
     * Layouts list
     * @var array
     */
    protected $layouts = [];

    /**
     * Active layouts
     * @var string
     */
    protected $activeLayout;

    /**
     * Class constructor
     *
     * @param array $layouts
     * @param array $activeLayouts
     */
    public function __construct(array $layouts, array $activeLayouts)
    {
        $this->layouts = $layouts;
        $this->activeLayout = end($activeLayouts)['name'];
    }

    /**
     * Get layouts list
     *
     * @return object LayoutList 
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Get layouts
     *
     * @return array
     */
    public function getLayouts()
    {
        return $this->layouts;
    }

    /**
     * Get layouts select box
     *
     * @param string $selectLinkId
     * @return string
     */
    public function getLayoutsSelectBox($selectLinkId)
    {
        return $this->getView()->partial('partial/layout-select', [
            'link_id' => $selectLinkId,
            'layouts' => $this->layouts,
            'active' => $this->activeLayout
        ]);
    }

    /**
     * Get acrtive layout
     *
     * @return string
     */
    public function getActiveLayout()
    {
        return $this->activeLayout;
    }
}