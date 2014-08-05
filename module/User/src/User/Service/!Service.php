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

        // init an ACL
        if (!self::$currentAcl) {
            self::initAcl();
        }

        $aclModel = self::$serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\Acl');

        // check the resource existing
        if (self::$currentAclResources && array_key_exists($resource, self::$currentAclResources)) {
            // check the resource's dates
            if (true === ($result = $aclModel->isAclResourceDatesActive(self::$currentAclResources[$resource]))) {
                // check the permission
                $permissionResult = self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource);

                // reset the current resource actions if it needs
                if (true === ($result = $aclModel->resetAclResource(self::$currentUserIdentity->
                        user_id, self::$currentAclResources[$resource], $permissionResult, $increaseActions))) {

                    // update ACL resources again
                    self::initAcl();

                    // check the permission again
                    if (true !== ($permissionResult = 
                            self::$currentAcl->isAllowed(self::$currentUserIdentity->role, $resource))) {

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
     * @return void
     */
    protected static function initAcl()
    {
        // create a new ACL role
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
                // add a new resource
                $acl->addResource(new Resource($resource['resource']));

                // add the resource's permission
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