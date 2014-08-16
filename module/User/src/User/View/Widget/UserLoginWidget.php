<?php
namespace User\View\Widget;

use Acl\Model\Base as AclModel;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\Event as UserEvent;

class UserLoginWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (UserIdentityService::isGuest()) {
            // get a login form
            $loginForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\Login');

            if ($this->getRequest()->isPost()) {
                // fill form with received values
                $loginForm->getForm()->setData($this->getRequest()->getPost());

                if ($loginForm->getForm()->isValid()) {
                    // check an authentication
                    $this->getAuthService()
                        ->getAdapter()
                        ->setIdentity($this->getRequest()->getPost('nickname'))
                        ->setCredential($this->getRequest()->getPost('password'));

                    $result = $this->getAuthService()->authenticate();

                    if ($result->isValid()) {
                        // get the user info
                        $userData = $this->getAuthService()->getAdapter()->getResultRowObject([
                            'user_id',
                            'nick_name'
                        ]);

                        $rememberMe = null != ($result = $this->getRequest()->getPost('remember')) 
                            ? true 
                            : false;

                        return $this->loginUser($userData->user_id, $userData->nick_name, $rememberMe);
                    }
                    else {
                        $this->getFlashMessenger()->setNamespace('error');

                        // add error messages
                        foreach ($result->getMessages() as $errorMessage) {
                            $errorMessage = $this->translate($errorMessage);
                            $this->getFlashMessenger()->addMessage($errorMessage);
                        }

                        // fire the user login failed event
                        UserEvent::fireLoginFailedEvent(AclModel::DEFAULT_ROLE_GUEST, $this->getRequest()->getPost('nickname'));
                        return $this->reloadPage();
                    }
                }
            }

            return $this->getView()->partial('user/widget/login', [
                'loginForm' => $loginForm->getForm()
            ]);
        }

        return false;
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->translate('Login');
    }
}