<?php

namespace Users\Service;

use Application\Service\Service as ApplicationService;
use Application\Model\Acl as AclModel;

use Zend\Permissions\Acl\Acl as Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

class Service extends ApplicationService
{
    /**
     * Acl resource space devider
     */
    const ACL_RESOURCE_SPACE_DEVIDER = '_';

    /**
     * Get user info
     *
     * @param integer $userId
     * @param boolean $isApiKey
     * @return array
     */
    public static function getUserInfo($userId, $isApiKey = false)
    {
        $user = self::$serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Users\Model\Base');

        return $user->getUserInfoById($userId, $isApiKey); 
    }

    /**
     * Check is admin or not
     *
     * @return boolean
     */
    public static function isAdmin()
    {
        return self::getCurrentUserIdentity()->user_id == AclModel::DEFAULT_ROLE_ADMIN;
    }

    /**
     * Check is guest or not
     *
     * @return boolean
     */
    public static function isGuest()
    {
        return self::getCurrentUserIdentity()->user_id == AclModel::DEFAULT_GUEST_ID;
    }

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

        // process a resource name
        $resource = str_replace(array(' ', '-'),
                array(self::ACL_RESOURCE_SPACE_DEVIDER, self::ACL_RESOURCE_SPACE_DEVIDER), $resource);

        // init a acl
        if (!self::$currentAcl) {
            self::initAcl();
        }

        // check the resource existing
        if (self::$currentAclResources && array_key_exists($resource, self::$currentAclResources)) {
            // check the permission
            $permissionResult = self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource);

            $currentTime = time();

            // check the date start is should be empty or active
            if (self::$currentAclResources[$resource]['date_start']
                        && self::$currentAclResources[$resource]['date_start'] > $currentTime) {

                return $permissionResult;
            }

            // check the date end is should be empty or active
            if (self::$currentAclResources[$resource]['date_end']
                        && self::$currentAclResources[$resource]['date_end'] < $currentTime) {

                return $permissionResult;
            }

            // check the resource's actions limit
            if (self::$currentAclResources[$resource]['actions_limit']) {
                $updateAclResources = false;

                // do we need reset all actions?
                if (self::$currentAclResources[$resource]['actions']) {
                    if (self::$currentAclResources[$resource]['actions_reset'] && $currentTime >=
                            self::$currentAclResources[$resource]['actions_last_reset'] +
                            self::$currentAclResources[$resource]['actions_reset']) {

                        // reset the resource's actions
                        $aclModel = self::$serviceManager
                            ->get('Application\Model\ModelManager')
                            ->getInstance('Application\Model\Acl');

                        $result = $aclModel->increaseAclAction(self::$currentUserIdentity->user_id,
                                self::$currentAclResources[$resource], true, ($increaseActions ? 1 : 0));

                        if (true !== $result) {
                            return false;
                        }

                        $updateAclResources = true;
                    }
                }

                // increase actions
                if ($increaseActions && !$updateAclResources && $permissionResult === true) {
                    // increase the resource's actions
                    $aclModel = self::$serviceManager
                        ->get('Application\Model\ModelManager')
                        ->getInstance('Application\Model\Acl');

                    $result = $aclModel->
                            increaseAclAction(self::$currentUserIdentity->user_id, self::$currentAclResources[$resource]);

                    if (true !== $result) {
                        return false;
                    }

                    $updateAclResources = true;
                }

                // update all acl resources
                if ($updateAclResources) {
                    self::initAcl();
                    return $permissionResult === false
                        ? self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource)
                        : $permissionResult;
                }
            }

            return $permissionResult;
        }

        return false;
    }

    /**
     * Init acl
     *
     * @return void
     */
    protected static function initAcl()
    {
        $acl = new Acl();
        $acl->addRole(new Role(self::$currentUserIdentity->role));

        $aclModel = self::$serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\Acl');

        // get assigned acl resources
        if (null != ($resources = $aclModel->
                getAclResources(self::$currentUserIdentity->role, self::$currentUserIdentity->user_id))) {

            // process acl resources
            $resourcesInfo = array();
            foreach ($resources as $resource) {
                // add new resource
                $acl->addResource(new Resource($resource['resource']));

                // add resource's action
                $resource['permission'] == AclModel::ACTION_ALLOWED
                    ? $acl->allow(self::$currentUserIdentity->role, $resource['resource'])
                    : $acl->deny(self::$currentUserIdentity->role, $resource['resource']);

                $resourcesInfo[$resource['resource']] = $resource;
            }

            self::$currentAclResources = $resourcesInfo;
        };

        self::$currentAcl = $acl;
    }
}