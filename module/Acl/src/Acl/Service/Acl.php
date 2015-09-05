<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Acl\Service;

use User\Service\UserIdentity as UserIdentityService;
use Acl\Model\AclBase as AclBaseModel;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
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
     *
     * @var \Zend\Permissions\Acl\AclInterface
     */
    protected static $currentAcl = null;

    /**
     * Current acl resources
     *
     * @var array
     */
    protected static $currentAclResources;

    /**
     * Acl roles
     *
     * @var array
     */
    protected static $aclRoles;

    /**
     * Get acl roles
     *
     * @param boolean $excludeGuest
     * @param boolean $excludeAdmin
     * @return array
     */
    public static function getAclRoles($excludeGuest = true, $excludeAdmin = false)
    {
        $paramsKey = (int) $excludeGuest . '_' . (int) $excludeAdmin;

        if (!isset(self::$aclRoles[$paramsKey])) {
            self::$aclRoles[$paramsKey] = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Acl\Model\AclBase')
                ->getRolesList($excludeGuest, $excludeAdmin);
        }

        return self::$aclRoles[$paramsKey];
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
     * @return array
     */
    public static function getCurrentAclResources()
    {
        return self::$currentAclResources;
    }

    /**
     * Set current acl
     *
     * @param \Zend\Permissions\Acl\AclInterface $acl
     * @return void
     */
    public static function setCurrentAcl(AclInterface $acl)
    {
        self::$currentAcl = $acl;
    }

    /**
     * Get current acl
     *
     * @return \Zend\Permissions\Acl\AclInterface
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

        $aclModel = ServiceLocatorService::getServiceLocator()
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

        $aclModel = ServiceLocatorService::getServiceLocator()
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