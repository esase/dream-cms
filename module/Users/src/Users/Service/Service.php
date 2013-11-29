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
     * Resource space devider
     */
    const RESOURCE_SPACE_DEVIDER = '_';

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

        // processing resource name
        $resource = str_replace(array(' ', '-'),
                array(self::RESOURCE_SPACE_DEVIDER, self::RESOURCE_SPACE_DEVIDER), $resource);

        // init acl
        if (!self::$currentAcl) {
            self::initAcl();
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

        // get acl resources
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