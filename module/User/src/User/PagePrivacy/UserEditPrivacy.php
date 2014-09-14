<?php
namespace User\PagePrivacy;

use Page\PagePrivacy\PageAbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;

class UserEditPrivacy extends PageAbstractPagePrivacy
{
    /**
     * Is allowed to view page
     *
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [])
    {
        return !UserIdentityService::isGuest();
    }
}