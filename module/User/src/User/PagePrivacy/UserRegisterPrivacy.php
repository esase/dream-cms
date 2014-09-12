<?php
namespace User\PagePrivacy;

use Page\PagePrivacy\AbstractPagePrivacy;
use User\Service\UserIdentity as UserIdentityService;
use Application\Service\Setting as SettingService;

class UserRegisterPrivacy extends AbstractPagePrivacy
{
    /**
     * Is allowed to view page
     *
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [])
    {
        return UserIdentityService::isGuest() && (int) SettingService::getSetting('user_allow_register');
    }
}