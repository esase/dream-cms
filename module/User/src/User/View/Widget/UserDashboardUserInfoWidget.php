<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;

class UserDashboardUserInfoWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (!UserIdentityService::isGuest()) {
            return $this->getView()->partial('user/widget/info', [
                'user' => UserIdentityService::getCurrentUserIdentity()
            ]);
        }

        return false;
    }
}