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
     * @return boolean
     */
    public static function checkPermission($resource)
    {
        if (self::$currentUserIdentity->role == AclModel::DEFAULT_ROLE_ADMIN) {
            return true;
        }

        // check resource existing
        if (array_key_exists($resource, self::$currentAclResources)) {
            $aclModel = self::$serviceManager
                ->get('Application\Model\Builder')
                ->getInstance('Application\Model\Acl');

            // check permission
            $permissionResult = self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource);

            $currentTime = time();

            // update actions counter
            if (self::$currentAclResources[$resource]->actions_limit) {
                // check expired date
                if (!self::$currentAclResources[$resource]->date_end ||
                        self::$currentAclResources[$resource]->date_end >= $currentTime) {

                    // check reset actions time
                    $resetActions = false;

                    if (self::$currentAclResources[$resource]->actions_reset && $currentTime >=
                            self::$currentAclResources[$resource]->actions_last_reset +
                            self::$currentAclResources[$resource]->actions_reset) {

                        $resetActions = true;
                    }

                    // increase actions counter
                    if ($resetActions || true == $permissionResult) {
                        echo 'update counter<br>';
                        $result = $aclModel->increaseAclAction(self::$currentAclResources[$resource]->id,
                                self::$currentUserIdentity->user_id, $resetActions);

                        $permissionResult = $result === true ?:false;
                    }
                }
            }

            return $permissionResult;
        }

        return false;
    }
}