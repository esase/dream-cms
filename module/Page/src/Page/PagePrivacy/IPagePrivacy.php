<?php
namespace Page\PagePrivacy;

use Zend\Mvc\Controller\AbstractController;

interface IPagePrivacy
{
    /**
     * Set controller
     *
     * @param object $controller
     * @return object fluent interface
     */
    //public function setController(AbstractController $controller);

    /**
     * Is allowed to view
     *
     * @return boolean
     */
    public function isAllowedViewPage();
}