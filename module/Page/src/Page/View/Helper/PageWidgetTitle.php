<?php
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageWidgetTitle extends AbstractHelper
{
    /**
     * Page widget title
     *
     * @param array $options
     *      string widget_title
     *      string widget_description
     * @return string
     */
    public function __invoke($options)
    {
        return !empty($options['widget_title'])
            ? $options['widget_title']
            : $this->getView()->translate($options['widget_description']);
    }
}