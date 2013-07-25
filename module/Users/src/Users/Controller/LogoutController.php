<?php

namespace Users\Controller;

class LogoutController extends BaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $this->getAuthService()->clearIdentity();
        $this->flashmessenger()
            ->setNamespace('success')
            ->addMessage($this->getTranslator()->translate('You\'ve been logged out'));

        return $this->redirect()->toRoute('application', array('controller' => 'login'));
    }
}