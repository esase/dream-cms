<?php

namespace User\Service;

use Application\Service\Service as ApplicationService;
use Application\Model\Acl as AclModelBase;
use User\Model\Base as UserBaseModel;

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
     * @param string $field
     * @return array
     */
    public static function getUserInfo($userId, $field = null)
    {
        return self::$serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\Base')
            ->getUserInfo($userId, $field);
    }

    /**
     * Check is admin or not
     *
     * @return boolean
     */
    public static function isAdmin()
    {
        return self::getCurrentUserIdentity()->user_id == AclModelBase::DEFAULT_ROLE_ADMIN;
    }

    /**
     * Check is default user or not
     *
     * @return boolean
     */
    public static function isDefaultUser()
    {
        return self::getCurrentUserIdentity()->user_id == UserBaseModel::DEFAULT_USER_ID;
    }

    /**
     * Check is guest or not
     *
     * @return boolean
     */
    public static function isGuest()
    {
        return self::getCurrentUserIdentity()->user_id == UserBaseModel::DEFAULT_GUEST_ID;
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
        if (self::$currentUserIdentity->role == AclModelBase::DEFAULT_ROLE_ADMIN) {
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

            // check the resource's actions limit
            if (self::$currentAclResources[$resource]['actions_limit']) {
                // check resource's dates states (the dates should be empty or active)
                if (true !== ($result = self::isResourceDatesActive($resource))) {
                    return $permissionResult;
                }

                $updateAclResources = false;

                // do we need reset all actions?
                if (self::$currentAclResources[$resource]['actions_reset'] && time() >=
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

                    if (true !== ($permissionResult =
                            self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource))) {

                        // check resource's dates
                        if (true === ($result = self::isResourceDatesActive($resource))) {
                            // a previous action should be finished
                            if ((int) self::$currentAclResources[$resource]['actions_limit'] ==
                                        (int) self::$currentAclResources[$resource]['actions']) {

                                return true;
                            }
                        }
                    }

                    return $permissionResult;
                }
            }

            return $permissionResult;
        }

        return false;
    }

    /**
     * Check resource's dates state
     *
     * @param string $resource
     * @return boolean
     */
    protected static function isResourceDatesActive($resource)
    {
        $currentTime = time();

        // a date start still not active
        if (self::$currentAclResources[$resource]['date_start'] &&
                    self::$currentAclResources[$resource]['date_start'] > $currentTime) {

            return false;
        }

        // a date end still not active
        if (self::$currentAclResources[$resource]['date_end'] &&
                    self::$currentAclResources[$resource]['date_end'] < $currentTime) {

            return false;
        }

        return true;
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
                $resource['permission'] == AclModelBase::ACTION_ALLOWED
                    ? $acl->allow(self::$currentUserIdentity->role, $resource['resource'])
                    : $acl->deny(self::$currentUserIdentity->role, $resource['resource']);

                $resourcesInfo[$resource['resource']] = $resource;
            }

            self::$currentAclResources = $resourcesInfo;
        };

        self::$currentAcl = $acl;
    }
}