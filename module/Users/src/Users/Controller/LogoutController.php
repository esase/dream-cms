<?php

namespace Users\Controller;

use Users\Event\Event as UsersEvent;
use Users\Service\Service as UsersService;

class LogoutController extends BaseController
{
    /**
     * Index page
     */
    public function indexAction()
    {
        $user = UsersService::getCurrentUserIdentity();

        if (!$this->isGuest()) {
            $this->getAuthService()->clearIdentity();
            $this->flashmessenger()
                ->setNamespace('success')
                ->addMessage($this->getTranslator()->translate('You\'ve been logged out'));

            UsersEvent::fireEvent(UsersEvent::USER_LOGOUT, $user->user_id,
                $user->user_id, 'Event - User successfully logged out', array($user->nick_name));
        }

        return $this->redirectTo('login');
    }
}