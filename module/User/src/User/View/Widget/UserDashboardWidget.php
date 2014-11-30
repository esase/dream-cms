<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;

class UserDashboardWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (!UserIdentityService::isGuest()) {
            return $this->getView()->partial('user/widget/dashboard', [
                'user' => UserIdentityService::getCurrentUserIdentity()
            ]);
        }

        return false;
    }
}