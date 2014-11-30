<?php

namespace Page\View\Helper;

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
        if (!empty($options['title'])) {
            return $this->getView()->escapeHtml($options['title']);
        }

        return $this->getView()->translate($options['system_title']);
    }
}