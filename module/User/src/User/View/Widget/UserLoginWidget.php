<?php
namespace User\View\Widget;

use Acl\Model\Base as AclModel;
use Page\View\Widget\AbstractWidget;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\Event as UserEvent;

class UserLoginWidget extends AbstractWidget
{
    /**
     * Auth service
     * @var object  
     */
    protected $authService;

    /**
     * Get auth service
     *
     * @return object
     */
    protected function getAuthService()
    {
        if (!$this->authService) {
            $this->authService = $this->getServiceLocator()->get('User\AuthService');
        }

        return $this->authService;
    }

    /**
     * Login user
     *
     * @param integer $userId
     * @param string $userNickname
     * @param boolean $rememberMe
     * @return string
     */
    protected function loginUser($userId, $userNickname, $rememberMe = false)
    {
        $user = [];
        $user['user_id'] = $userId;

        // save user id
        $this->getAuthService()->getStorage()->write($user);

        // fire the user login event
        UserEvent::fireLoginEvent($userId, $userNickname);

        if ($rememberMe) {
            $this->serviceLocator->
                    get('Zend\Session\SessionManager')->rememberMe((int) $this->getSetting('user_session_time'));
        }

        return $this->redirectTo(); // redirect to home page
    }

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

            $request  = $this->getServiceLocator()->get('Request');

            if ($request->isPost()) {
                // fill form with received values
                $loginForm->getForm()->setData($request->getPost());

                if ($loginForm->getForm()->isValid()) {
                    // check an authentication
                    $this->getAuthService()
                        ->getAdapter()
                        ->setIdentity($request->getPost('nickname'))
                        ->setCredential($request->getPost('password'));

                    $result = $this->getAuthService()->authenticate();

                    if ($result->isValid()) {
                        // get the user info
                        $userData = $this->getAuthService()->getAdapter()->getResultRowObject([
                            'user_id',
                            'nick_name'
                        ]);

                        $rememberMe = null != ($result = $request->getPost('remember')) 
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
                        UserEvent::fireLoginFailedEvent(AclModel::DEFAULT_ROLE_GUEST, $request->getPost('nickname'));
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