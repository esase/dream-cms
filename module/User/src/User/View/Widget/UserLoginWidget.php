<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;
use User\Utility\UserAuthenticate as UserAuthenticateUtility;

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
                ->getInstance('User\Form\UserLogin');

            if ($this->getRequest()->isPost() &&
                    $this->getRequest()->getPost('form_name') == $loginForm->getFormName()) {

                // fill form with received values
                $loginForm->getForm()->setData($this->getRequest()->getPost());

                if ($loginForm->getForm()->isValid()) {
                    $userName = $this->getRequest()->getPost('nickname');
                    $password = $this->getRequest()->getPost('password');

                    // check an authentication
                    $authErrors = [];
                    $result = UserAuthenticateUtility::
                            isAuthenticateDataValid($userName, $password, $authErrors);

                    if (false === $result) {
                        $this->getFlashMessenger()->setNamespace('error');

                        // add auth error messages
                        foreach ($authErrors as $message) {
                            $this->getFlashMessenger()->addMessage($this->translate($message));
                        }

                        return $this->reloadPage();
                    }

                    $rememberMe = null != ($remember = $this->getRequest()->getPost('remember')) 
                        ? true 
                        : false;

                    return $this->loginUser($result['user_id'], $result['nick_name'], $rememberMe);
                }
            }

            return $this->getView()->partial('user/widget/login', [
                'loginForm' => $loginForm->getForm()
            ]);
        }

        return false;
    }
}