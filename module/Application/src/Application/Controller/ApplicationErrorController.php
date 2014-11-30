<?php
namespace Application\Controller;

use Zend\Http\Response;

class ApplicationErrorController extends ApplicationAbstractBaseController
{
    /**
     * Forbidden page
     */
    public function forbiddenAction()
    {
        $this->layout('layout/error');
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_403);
    }
}
