<?php
namespace Acl\Service;

use User\Service\UserIdentity as UserIdentityService;
use Acl\Model\AclBase as AclBaseModel;
use Application\Service\ApplicationServiceManager as ServiceManagerService;

use Zend\Permissions\Acl\AclInterface;
use Zend\Permissions\Acl\Acl as AclZend;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;

class Acl
{
    /**
     * Acl resource space devider
     */
    const ACL_RESOURCE_SPACE_DEVIDER = '_';

    /**
     * Current acl
     * @var object
     */
    protected static $currentAcl = null;

    /**
     * Current acl resources
     * @var object
     */
    protected static $currentAclResources;

    /**
     * Acl roles
     * @var array
     */
    protected static $aclRoles;

    /**
     * Get acl roles
     *
     * @param boolean $excludeGuest
     * @return array
     */
    public static function getAclRoles($excludeGuest = true)
    {
        if (!isset(self::$aclRoles[$excludeGuest])) {
            self::$aclRoles[$excludeGuest] = ServiceManagerService::getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('Acl\Model\AclBase')
                ->getRolesList($excludeGuest);
        }

        return self::$aclRoles[$excludeGuest];
    }

    /**
     * Set current acl resources
     *
     * @param array $resources
     * @return void
     */
    public static function setCurrentAclResources(array $resources)
    {
        self::$currentAclResources = $resources;    
    }

    /**
     * Get current acl resources
     *
     * @return object
     */
    public static function getCurrentAclResources()
    {
        return self::$currentAclResources;
    }

    /**
     * Set current acl
     *
     * @param object $acl
     * @return void
     */
    public static function setCurrentAcl(AclInterface $acl)
    {
        self::$currentAcl = $acl;
    }

    /**
     * Get current acl
     *
     * @return object
     */
    public static function getCurrentAcl()
    {
        return self::$currentAcl;
    }

    /**
     * Clear current acl
     *
     * @return void
     */
    public static function clearCurrentAcl()
    {
        return self::$currentAcl = null;
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
        $currentUserIdentity = UserIdentityService::getCurrentUserIdentity();

        // admin can do everything
        if ($currentUserIdentity['role'] == AclBaseModel::DEFAULT_ROLE_ADMIN) {
            return true;
        }

        // process a resource name
        $resource = str_replace([' ', '-'],
                [self::ACL_RESOURCE_SPACE_DEVIDER, self::ACL_RESOURCE_SPACE_DEVIDER], $resource);

        // init an ACL
        if (null === self::$currentAcl) {
            self::initAcl($currentUserIdentity);
        }

        $aclModel = ServiceManagerService::getServiceManager()
            ->get('Application\Model\ModelManager')
            ->getInstance('Acl\Model\AclBase');

        // check the resource existing
        if (self::$currentAclResources && array_key_exists($resource, self::$currentAclResources)) {
            // check the resource's dates
            if (true === ($result = $aclModel->isAclResourceDatesActive(self::$currentAclResources[$resource]))) {
                // check the permission
                $permissionResult = self::$currentAcl->isAllowed($currentUserIdentity['role'], $resource);

                // reset the current resource actions if it needs
                if (true === ($result = $aclModel->resetAclResource($currentUserIdentity['user_id'], 
                        self::$currentAclResources[$resource], $permissionResult, $increaseActions))) {

                    // update ACL resources again
                    self::initAcl($currentUserIdentity);

                    // check the permission again
                    if (true !== ($permissionResult = 
                            self::$currentAcl->isAllowed($currentUserIdentity['role'], $resource))) {

                        // check the resource's dates
                        if (true === ($result = $aclModel->isAclResourceDatesActive(self::$currentAclResources[$resource]))) {
                            // a previous action should be finished
                            if ((int) self::$currentAclResources[$resource]['actions_limit'] == (int) 
                                    self::$currentAclResources[$resource]['actions']) {

                                return true;
                            }
                        }
                    }
                }

                return $permissionResult;
            }
        }

        return false;
    }

    /**
     * Init acl
     *
     * @param array $currentUserIdentity
     * @return void
     */
    protected static function initAcl(array $currentUserIdentity)
    {
        // create a new ACL role
        $acl = new AclZend();
        $acl->addRole(new Role($currentUserIdentity['role']));

        $aclModel = ServiceManagerService::getServiceManager()
            ->get('Application\Model\ModelManager')
            ->getInstance('Acl\Model\AclBase');

        // get assigned acl resources
        if (null != ($resources = $aclModel->
                getAclResources($currentUserIdentity['role'], $currentUserIdentity['user_id']))) {

            // process acl resources
            $resourcesInfo = [];
            foreach ($resources as $resource) {
                // add a new resource
                $acl->addResource(new Resource($resource['resource']));

                // add the resource's permission
                $resource['permission'] == AclBaseModel::ACTION_ALLOWED
                    ? $acl->allow($currentUserIdentity['role'], $resource['resource'])
                    : $acl->deny($currentUserIdentity['role'], $resource['resource']);

                $resourcesInfo[$resource['resource']] = $resource;
            }

            self::$currentAclResources = $resourcesInfo;
        };

        self::$currentAcl = $acl;
    }
}