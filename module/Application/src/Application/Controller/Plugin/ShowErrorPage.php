<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Http\Response;

/**
 * Controller plugin for showing an error page.
 */
class ShowErrorPage extends AbstractPlugin
{
    /**
     * Show an error page
     *
     * @param string $action
     * @return void
     */
    public function __invoke($action = 'forbidden')
    {
        $this->getController()->getResponse()->setStatusCode(Response::STATUS_CODE_302);
        $this->getController()->plugin('Redirect')->toRoute('application', array(
            'controller' => 'error',
            'action' => $action
        ));
    }
}