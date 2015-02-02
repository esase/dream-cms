<?php
namespace User\Controller;

use Layout\Utility\LayoutCookie as LayoutCookieUtility;
use Layout\Service\Layout as LayoutService;
use Application\Controller\ApplicationAbstractBaseController;
use User\Service\UserIdentity as UserIdentityService;
use User\Event\UserEvent;
use Zend\View\Model\ViewModel;

class UserAjaxController extends ApplicationAbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserAjax');
        }

        return $this->model;
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
        UserIdentityService::getAuthService()->clearIdentity();
        $this->serviceLocator->get('Zend\Session\SessionManager')->rememberMe(0);

        // fire the user logout event
        UserEvent::fireLogoutEvent($user['user_id'], $user['nick_name']);

        return $this->getResponse();
    }

    /**
     * Select layout
     */
    public function ajaxSelectLayoutAction()
    {
        $request  = $this->getRequest();

        if ($request->isPost()) {
            if ((int) $this->applicationSetting('layout_select')) {
                $layoutId = $this->getSlug(-1);
                $layouts = LayoutService::getLayouts(false);

                // save selected layout
                if (array_key_exists($layoutId, $layouts)) {
                    if (!$this->isGuest()) {
                        $user = UserIdentityService::getCurrentUserIdentity();
                        $this->getModel()->selectLayout($layoutId, $user['user_id']);
                    }

                    LayoutCookieUtility::saveLayout($layoutId);
                }
            }
        }

        return $this->getResponse();
    }
}