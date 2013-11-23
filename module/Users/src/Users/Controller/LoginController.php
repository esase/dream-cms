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
use Users\Event\Event as UsersEvent;

class LoginController extends BaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $userIdentity = $this->getAuthService()->getIdentity();

        // if already login, redirect to success page
        if (!$this->isGuest()){
            return $this->redirect()->toRoute('application');
        }

        // generate login form
        $request  = $this->getRequest();

        // get login form
        $loginForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Users\Form\Login');

        if ($request->isPost()) {
            // fill form with received values
            $loginForm->getForm()->setData($request->getPost());

            if ($loginForm->getForm()->isValid()) {
                // check authentication
                $this->getAuthService()->getAdapter()
                   ->setIdentity($request->getPost('nickname'))
                   ->setCredential($request->getPost('password'));

                $result = $this->getAuthService()->authenticate(); 
                if ($result->isValid()) {
                    // save user id
                    $this->getAuthService()->getStorage()->
                            write($this->getAuthService()->getAdapter()->getResultRowObject(array('user_id')));

                    // get updated Identity again 
                    $userIdentity = $this->getAuthService()->getIdentity();

                    // fire event
                    UsersEvent::fireEvent(UsersEvent::USER_LOGIN, $userIdentity->user_id,
                            $userIdentity->user_id, 'Event - User successfully logged', array($request->getPost('nickname')));

                    //TODO: there is need to check referrer 
                    return $this->redirect()->toRoute('application');
                }
                else {
                    // generate error messages
                    $this->flashMessenger()->setNamespace('error');

                    foreach ($result->getMessages() as $errorMessage) {
                        $errorMessage = $this->getTranslator()->translate($errorMessage);
                        $this->flashMessenger()->addMessage($errorMessage);
                    }

                    // fire event
                    UsersEvent::fireEvent(UsersEvent::USER_LOGIN_FAILED, 0,
                            Acl::DEFAULT_ROLE_GUEST, 'Event - User login failed message', array($request->getPost('nickname')));

                    return $this->redirect()->toRoute('application', array('controller' => 'login'));
                }
            } 
        }

        return new ViewModel(array(
            'loginForm' => $loginForm->getForm()
        ));
    }
}
