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
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [])
    {
        return UserIdentityService::isGuest();
    }
}