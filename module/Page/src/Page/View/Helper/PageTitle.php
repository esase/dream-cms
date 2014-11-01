<?php
namespace Page\View\Helper;

use Page\Model\PageNestedSet;
use Zend\View\Helper\AbstractHelper;

class PageTitle extends AbstractHelper
{
    /**
     * Page title
     *
     * @param array $options
     *      string title
     *      string system_title
     *      string type
     * @return string
     */
    public function __invoke($options)
    {
        return PageNestedSet::PAGE_TYPE_SYSTEM == $options['type']
            ? $this->getView()->translate($options['system_title']) 
            : $this->getView()->escapeHtml($options['title']);
    }
}