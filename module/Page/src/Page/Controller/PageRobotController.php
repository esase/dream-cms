<?php
namespace Page\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PageRobotController extends AbstractActionController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $this->getResponse()->getHeaders()->addHeaders(['Content-type' => 'text/plain']);

        $view = new ViewModel();
        $view->setTerminal(true);

        return $view;
    }
}