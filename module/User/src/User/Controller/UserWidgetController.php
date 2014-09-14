<?php
namespace User\Controller;

use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;
use Zend\View\Model\ViewModel;

class UserWidgetController extends ApplicationAbstractBaseController
{
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
            $this->authService = $this->getServiceLocator()->get('User\AuthService');
        }

        return $this->authService;
    }

    /**
     * Logout 
     */
    public function ajaxLogoutAction()
    {
        $request  = $this->getRequest();

        if ($this->isGuest() || !$request->isPost()) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // clear logged user's identity
        $user = UserIdentityService::getCurrentUserIdentity();
        $this->getAuthService()->clearIdentity();
        $this->serviceLocator->get('Zend\Session\SessionManager')->rememberMe(0);

        // fire the user logout event
        UserEvent::fireLogoutEvent($user['user_id'], $user['nick_name']);

        return $this->getResponse();
    }
}