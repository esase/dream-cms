<?php

namespace Users\Service;

use Application\Service\Service as ApplicationService;
use Application\Model\Acl as AclModel;

class Service extends ApplicationService
{
    /**
     * Check permission
     *
     * @param string $resource
     * @param boolean $increaseActions
     * @return boolean
     */
    public static function checkPermission($resource, $increaseActions = true)
    {
        // admin can do everything
        if (self::$currentUserIdentity->role == AclModel::DEFAULT_ROLE_ADMIN) {
            return true;
        }

        // check resource existing
        if (self::$currentAclResources && array_key_exists($resource, self::$currentAclResources)) {
            // check permission
            $permissionResult = self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource);

            // update actions counter
            if (self::$currentAclResources[$resource]['actions_limit'] && $increaseActions) {
                $currentTime = time();

                // check expired date (expired date must be active)
                if (!self::$currentAclResources[$resource]['date_end'] ||
                        self::$currentAclResources[$resource]['date_end'] >= $currentTime) {

                    // check reset actions time
                    $resetActions = false;

                    if (self::$currentAclResources[$resource]['actions_reset'] && $currentTime >=
                            self::$currentAclResources[$resource]['actions_last_reset'] +
                            self::$currentAclResources[$resource]['actions_reset']) {

                        $resetActions = true;
                    }

                    // increase actions counter
                    if ($resetActions || true == $permissionResult) {
                        $aclModel = self::$serviceManager
                            ->get('Application\Model\ModelManager')
                            ->getInstance('Application\Model\Acl');

                        $result = $aclModel->increaseAclAction(self::$currentUserIdentity->user_id,
                                self::$currentAclResources[$resource], $resetActions);

                        $permissionResult = $result === true ?:false;
                    }
                }
            }

            return $permissionResult;
        }

        return false;
    }
}