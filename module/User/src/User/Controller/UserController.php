<?php
namespace User\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use User\Model\User as UserModel;
use StdClass;
use User\Event\Event as UserEvent;
use Application\Utility\EmailNotification;
use User\Service\Service as UserService;
use Application\Model\Acl as AclModel;

class UserController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Auth service
     * @var object  
     */
    protected $authService;

    /**
     * Get auth service
     */
    protected function getAuthService()
    {
        if (!$this->authService) {
            $this->authService = $this->getServiceLocator()->get('Application\AuthService');
        }

        return $this->authService;
    }

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\User');
        }

        return $this->model;
    }

    /**
     * Logout user
     */
    protected function logoutUser()
    {
        $user = UserService::getCurrentUserIdentity();

        // clear logged user's identity
        $this->getAuthService()->clearIdentity();

        // skip a remember me time
        $this->serviceLocator->get('Zend\Session\SessionManager')->rememberMe(0);

        // fire the user logout event
        UserEvent::fireLogoutEvent($user->user_id, $user->nick_name);
    }

    /**
     * Login user
     *
     * @param integer $userId
     * @param string $userNickname
     * @param boolean $rememberMe
     * @param string $backUrl
     * @return string
     */
    protected function loginUser($userId, $userNickname, $rememberMe = false, $backUrl = null)
    {
        $user = new StdClass();
        $user->user_id = $userId;
        
        // save user id
        $this->getAuthService()->getStorage()->write($user);

        // fire the user login event
        UserEvent::fireLoginEvent($userId, $userNickname);

        if ($rememberMe) {
            $this->serviceLocator->
                    get('Zend\Session\SessionManager')->rememberMe((int) $this->getSetting('user_session_time'));
        }

        return $backUrl
            ? $this->redirect()->toUrl($backUrl)
            : $this->redirectTo(); // redirect to home page
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        return $this->redirectTo('user', 'login');
    }

    /**
     * Activate a user
     */
    public function activateAction()
    {
        // get user info
        if (!$this->isGuest() || null == ($userInfo =
                $this->getModel()->getUserInfo($this->getSlug(), UserModel::USER_INFO_BY_SLUG))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // check the user's status
        if ($userInfo['status'] != UserModel::STATUS_DISAPPROVED) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an activate form
        $activateForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\ActivationCode')
            ->setModel($this->getModel())
            ->setUserId($userInfo['user_id']);

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $activateForm->getForm()->setData($request->getPost(), false);

            // activate the users's status
            if ($activateForm->getForm()->isValid()) {
                // approve the user
                if (true !== ($approveResult = $this->getModel()->setUserStatus($userInfo['user_id']))) {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));

                    return $this->redirectTo('user', 'activate', array('slug' => $this->getSlug()));
                }

                // fire the approve user event
                UserEvent::fireUserApproveEvent($userInfo['user_id'], $userInfo, $userInfo['nick_name']);

                // login and redirect the user
                return $this->loginUser($userInfo['user_id'], $userInfo['nick_name']);
            }
        }

        return new ViewModel(array(
            'activateForm' => $activateForm->getForm(),
            'slug' => $this->getSlug()
        ));
    }

    /**
     * Password reset
     */
    public function passwordResetAction()
    {
        // get user info
        if (!$this->isGuest() || null == ($userInfo =
                $this->getModel()->getUserInfo($this->getSlug(), UserModel::USER_INFO_BY_SLUG))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a reset form
        $resetForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\ActivationCode')
            ->setModel($this->getModel())
            ->setUserId($userInfo['user_id']);

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $resetForm->getForm()->setData($request->getPost(), false);

            // reset the users's password
            if ($resetForm->getForm()->isValid()) {
                $resetedPassword = $this->getModel()->resetUserPassword($userInfo['user_id']);

                if (is_array($resetedPassword)) {
                    // fire the user password reset event
                    UserEvent::fireUserPasswordResetEvent($userInfo['user_id'], $userInfo, $resetedPassword['password']);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('We have sent a new password to your email'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));
                }

                return $this->redirectTo('user', 'password-reset', array(
                    'slug' => $this->getSlug()
                ));
            }
        }

        return new ViewModel(array(
            'resetForm' => $resetForm->getForm(),
            'slug' => $this->getSlug()
        ));
    }

    /**
     * Logout
     */
    public function logoutAction()
    {
        if ($this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // clear user's identity
        $this->logoutUser();

        $this->flashmessenger()
            ->setNamespace('success')
            ->addMessage($this->getTranslator()->translate('You\'ve been logged out'));

        return $this->redirectTo('user', 'login');
    }

    /**
     * Login
     */
    public function loginAction()
    {
        if (!$this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a login form
        $loginForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\Login');

        $request  = $this->getRequest();

        if ($request->isPost()) {
            // fill form with received values
            $loginForm->getForm()->setData($request->getPost());

            if ($loginForm->getForm()->isValid()) {
                // check an authentication
                $this->getAuthService()->getAdapter()
                   ->setIdentity($request->getPost('nickname'))
                   ->setCredential($request->getPost('password'));

                $result = $this->getAuthService()->authenticate();

                if ($result->isValid()) {
                    // get the user info
                    $userData = $this->getAuthService()->getAdapter()->getResultRowObject(array(
                        'user_id',
                        'nick_name'
                    ));

                    $rememberMe = null != ($result = $request->getPost('remember'))
                        ? true
                        : false;

                    return $this->loginUser($userData->user_id,
                            $userData->nick_name, $rememberMe, $request->getQuery('back'));
                }
                else {
                    // generate error messages
                    $this->flashMessenger()->setNamespace('error');

                    foreach ($result->getMessages() as $errorMessage) {
                        $errorMessage = $this->getTranslator()->translate($errorMessage);
                        $this->flashMessenger()->addMessage($errorMessage);
                    }

                    // fire the user login failed event
                    UserEvent::fireLoginFailedEvent(AclModel::DEFAULT_ROLE_GUEST, $request->getPost('nickname'));

                    return $this->redirectTo('user', 'login',
                            array(), false, array('back' => $request->getQuery('back')));
                }
            }
        }

        return new ViewModel(array(
            'back' => $request->getQuery('back'),
            'loginForm' => $loginForm->getForm()
        ));
    }

    /**
     * Delete the user
     */
    public function deleteAction()
    {
        if (true !== ($result = $this->isAutorized())) {
            return $result;
        }

        // additional checking
        if (UserService::isDefaultUser()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the user delete form
        $deleteForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\Delete');

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $deleteForm->getForm()->setData($request->getPost(), false);

            // delete the user's account
            if ($deleteForm->getForm()->isValid()) {
                // fire the delete user event
                UserEvent::fireUserDeleteEvent(UserService::getCurrentUserIdentity()->user_id);

                if (true !== ($deleteResult = $this->getModel()->deleteUser((array) UserService::getCurrentUserIdentity()))) {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));

                    return $this->redirectTo('user', 'delete');
                }
                else {
                    // clear user's identity
                    $this->logoutUser();

                    // redirect to home page
                    return $this->redirectTo();
                }
            }
        }

        return new ViewModel(array(
            'deleteForm' => $deleteForm->getForm()
        ));
    }

    /**
     * Edit the user
     */
    public function editAction()
    {
        if (true !== ($result = $this->isAutorized())) {
            return $result;
        }

        // get the user form
        $userForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\User')
            ->setModel($this->getModel())
            ->setUserId(UserService::getCurrentUserIdentity()->user_id)
            ->setUserAvatar(UserService::getCurrentUserIdentity()->avatar);

        // fill the form with default values
        $userForm->getForm()->setData((array) UserService::getCurrentUserIdentity());
        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $userForm->getForm()->setData($post, false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // set status
                $status = (int) $this->getSetting('user_auto_confirm') ||
                        UserService::getCurrentUserIdentity()->role ==  AclModel::DEFAULT_ROLE_ADMIN ? true : false;

                $deleteAvatar = (int) $this->getRequest()->getPost('avatar_delete')
                    ? true
                    : false;

                if (true == ($result = $this->getModel()->editUser((array) UserService::getCurrentUserIdentity(),
                        $userForm->getForm()->getData(), $status, $this->params()->fromFiles('avatar'), $deleteAvatar))) {

                    // fire the edit user event
                    UserEvent::fireUserEditEvent(UserService::getCurrentUserIdentity()->user_id, true);

                    if ($status) {
                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Your account has been edited'));
                    }
                    else {
                        // clear user's identity
                        $this->logoutUser();

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Your account will be active after checking'));

                        return $this->redirectTo('user', 'login');
                    }
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));
                }

                return $this->redirectTo('user', 'edit');
            }
        }

        return new ViewModel(array(
            'userForm' => $userForm->getForm()
        ));
    }

    /**
     * Forgot
     */
    public function forgotAction()
    {
        if (!$this->isGuest()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get a forgot form
        $forgotForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\Forgot')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $forgotForm->getForm()->setData($request->getPost(), false);

            if ($forgotForm->getForm()->isValid()) {
                // get an user info
                $userInfo = $this->getModel()->
                        getUserInfo($forgotForm->getForm()->getData()['email'], UserModel::USER_INFO_BY_EMAIL);

                // genereate a new activation code
                $activationCode = $this->getModel()->generateActivationCode($userInfo['user_id']);

                if (is_array($activationCode)) {
                    // fire the user password reset request event
                    UserEvent::fireUserPasswordResetRequestEvent($userInfo['user_id'], $userInfo, $activationCode['activation_code']);

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('We sent a message with a confirmation code. You should confirm the password reset'));

                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));
                }

                return $this->redirectTo('user', 'forgot');
            }
        }

        return new ViewModel(array(
            'forgotForm' => $forgotForm->getForm()
        ));
    }

    /**
     * Register a new user
     */
    public function registerAction()
    {
        if (!$this->isGuest() ||
                null == ($result = $this->getSetting('user_allow_register'))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an user form
        $userForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\User')
            ->setModel($this->getModel())
            ->showCaptcha(true);

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $userForm->getForm()->setData($post, false);

            // save data
            if ($userForm->getForm()->isValid()) {
                // add a new user with a particular status
                $status = (int) $this->getSetting('user_auto_confirm') ? true : false;

                $result = $this->getModel()->
                        addUser($userForm->getForm()->getData(), $status, $this->params()->fromFiles('avatar'));

                // the user has been added
                if (is_numeric($result)) {
                    // get the user info
                    $userInfo = $this->getModel()->getUserInfo($result);

                    // fire the add user event
                    UserEvent::fireUserAddEvent($result, $userInfo);

                    // check the user status
                    if (!$status) {
                        // send an email activate notification
                        EmailNotification::sendNotification($userInfo['email'],
                            $this->getSetting('user_email_confirmation_title'),
                            $this->getSetting('user_email_confirmation_message'), array(
                                'find' => array(
                                    'RealName',
                                    'SiteName',
                                    'ConfirmationLink',
                                    'ConfCode'
                                ),
                                'replace' => array(
                                    $userInfo['nick_name'],
                                    $this->getSetting('application_site_name'),
                                    $this->url()->fromRoute('application', array('controller' => 'user',
                                            'action' => 'activate', 'slug' => $userInfo['slug']), array('force_canonical' => true)),

                                    $userInfo['activation_code']
                                )
                            ));

                        $this->flashMessenger()
                              ->setNamespace('success')
                              ->addMessage($this->getTranslator()->translate('We sent a message with a confirmation code to your registration e-mail'));

                    }
                    else {
                        // login and redirect the registered user
                        return $this->loginUser($result, $userInfo['nick_name'], false, $request->getQuery('back'));
                    }
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('Error occurred'));
                }

                return $this->redirectTo('user', 'register',
                            array(), false, array('back' => $request->getQuery('back')));
            }
        }

        return new ViewModel(array(
            'back' => $request->getQuery('back'),
            'userForm' => $userForm->getForm()
        ));
    }
}