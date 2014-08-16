<?php
namespace User\PagePrivacy;

use Page\PagePrivacy\AbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;

class UserLoginPrivacy extends AbstractPagePrivacy
{
    /**
     * Is allowed to view page
     *
     * @return boolean
     */
    public function isAllowedViewPage()
    {
        return UserIdentityService::isGuest();
    }
}