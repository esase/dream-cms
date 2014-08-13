<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;

class UserActivateWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (UserIdentityService::isGuest()) {
            return 'aaa';
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
        return $this->translate('User activate');
    }
}