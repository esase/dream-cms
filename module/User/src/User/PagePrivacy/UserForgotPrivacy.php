<?php
namespace User\PagePrivacy;

use Page\PagePrivacy\AbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;
use Application\Service\ServiceManager as ServiceManagerService;

class UserForgotPrivacy extends AbstractPagePrivacy
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