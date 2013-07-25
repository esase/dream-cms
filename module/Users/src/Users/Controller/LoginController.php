<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Users\Controller;

use Zend\View\Model\ViewModel;
use Application\Model\Acl as Acl;
use Users\Form\LoginForm; 

class LoginController extends BaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $userIdentity = $this->getAuthService()->getIdentity();
        
        // if already login, redirect to success page
        if ($userIdentity->role !== Acl::DEFAULT_ROLE_GUEST){
            return $this->redirect()->toRoute('home');
        }

        // generate login form
        $request  = $this->getRequest();
        $loginForm = new LoginForm($this->getTranslator());

        if ($request->isPost()) {
            // fill form with received values
            $loginForm->setData($request->getPost());

            if ($loginForm->isValid()) {
                // check authentication
                $this->getAuthService()->getAdapter()
                   ->setIdentity($request->getPost('nickname'))
                   ->setCredential($request->getPost('password'));

                $result = $this->getAuthService()->authenticate(); 
                if ($result->isValid()) {
                    $this->getAuthService()->getStorage()->write($this->
                            getAuthService()->getAdapter()->getResultRowObject(null, array('password', 'salt')));

                    return $this->redirect()->toRoute('home');
                }
                else {
                    // generate error messages
                    $this->flashMessenger()->setNamespace('error');

                    foreach ($result->getMessages() as $errorMessage) {
                        $errorMessage = $this->getTranslator()->translate($errorMessage);
                        $this->flashMessenger()->addMessage($errorMessage);
                    }

                    return $this->redirect()->toRoute('application', array('controller' => 'login'));
                }
            } 
        }

        return new ViewModel(array(
            'loginForm' => $loginForm
        ));
    }
}
