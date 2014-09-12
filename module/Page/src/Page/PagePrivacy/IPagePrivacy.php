<?php
namespace Page\PagePrivacy;

use Zend\Mvc\Controller\AbstractController;

interface IPagePrivacy
{
    /**
     * Is allowed to view
     *
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = []);
}