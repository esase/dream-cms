<?php
namespace Application\Utility;

use Acl\Model\AclBase as AclBaseModel;
use Application\Service\ApplicationSetting as SettingService;
use User\Service\UserIdentity as UserIdentityService;
use Zend\Http\PhpEnvironment\RemoteAddress;

class ApplicationDisableSite
{
    /**
     * Is allowed to view the site
     *
     * @return boolean
     */
    public static function isAllowedViewSite()
    {
        if ((int) SettingService::getSetting('application_disable_site')) {
            $user = UserIdentityService::getCurrentUserIdentity();

            if ($user['role'] != AclBaseModel::DEFAULT_ROLE_ADMIN) {
                // get a visitor IP
                $remote = new RemoteAddress;
                $remote->setUseProxy(true);

                $userIp = $remote->getIpAddress();

                // get list of allowed ACL roles
                if (null != ($allowedAclRoles = SettingService::getSetting('application_disable_site_acl'))) {
                    if (!is_array($allowedAclRoles)) {
                        $allowedAclRoles = [$allowedAclRoles];
                    }
                }

                // get list of allowed IPs
                if (null != ($allowedIps = SettingService::getSetting('application_disable_site_ip'))) {
                    $allowedIps = explode(',', $allowedIps);
                }

                if ($allowedAclRoles || $allowedIps) {
                    if (($allowedAclRoles && in_array($user['role'], $allowedAclRoles)) 
                            || ($allowedIps && in_array($userIp, $allowedIps))) {

                        return true;
                    }
                }

                return false;
            }
        }

        return true;
    }
}