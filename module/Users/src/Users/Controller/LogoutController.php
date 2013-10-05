<?php

namespace Users\Controller;

use Users\Event\Event as UsersEvent;
use Users\Service\Service as UserService;
use Application\Model\Acl as AclModel;

class LogoutController extends BaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $user = UserService::getCurrentUserIdentity();

        if ($user->role != AclModel::DEFAULT_ROLE_GUEST) {
            $this->getAuthService()->clearIdentity();
            $this->flashmessenger()
                ->setNamespace('success')
                ->addMessage($this->getTranslator()->translate('You\'ve been logged out'));

            UsersEvent::fireEvent(UsersEvent::USER_LOGOUT, $user->user_id,
                $user->user_id, 'User successfully been logged out', array($user->nick_name));
        }

        return $this->redirect()->toRoute('application', array('controller' => 'login'));
    }
}