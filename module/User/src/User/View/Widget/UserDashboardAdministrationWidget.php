<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;

class UserDashboardAdministrationWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (!UserIdentityService::isGuest()
                    && $adminMenu = $this->getView()->applicationAdminMenu()) {

            return $this->getView()->partial('user/widget/administration', [
                'menu' => $adminMenu
            ]);
        }

        return false;
    }
}