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
namespace Acl\Test\Service;

use Acl\Test\AclBootstrap;
use Acl\Model\AclBase as AclModelBase;
use User\Service\UserIdentity as UserIdentityService;
use Acl\Service\Acl as AclService;
use Zend\Permissions\Acl\Acl as AclZend;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Math\Rand;
use Zend\Db\Sql\Expression as Expression;
use PHPUnit_Framework_TestCase;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Acl model
     *
     * @var \Acl\Model\AclBase
     */
    protected $aclModelBase;

    /**
     * User Id
     *
     * @var integer
     */
    protected $userId;

    /**
     * Acl resources ids
     *
     * @var array
     */
    protected $aclResourcesIds;

    /**
     * Acl resources connections ids
     *
     * @var array
     */
    protected $aclResourcesConnections;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = AclBootstrap::getServiceLocator();

        // get acl model
        $this->aclModelBase = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('Acl\Model\AclBase');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete test user
        if ($this->userId) {
            $query = $this->aclModelBase->delete()
                ->from('user_list')
                ->where(['user_id' => $this->userId]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->userId = null;
        }

        // delete acl test resources 
        if ($this->aclResourcesIds) {
            $query = $this->aclModelBase->delete()
                ->from('acl_resource')
                ->where(['id' => $this->aclResourcesIds]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->aclResourcesIds = [];
        }
    }

    /**
     * Add acl resources
     *
     * @param array $resources
     * @param boolean $createConnections
     * @param integer $userRole
     */
    protected function addAclResources($resources, $createConnections = true, $userRole = AclModelBase::DEFAULT_ROLE_MEMBER)
    {
        // create a test user
        $userData = [
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => $userRole
        ];

        // add member
        $query = $this->aclModelBase->insert()
            ->into('user_list')
            ->values($userData);

        $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->userId = $this->aclModelBase->getAdapter()->getDriver()->getLastGeneratedValue();

        // create new resources
        foreach ($resources as $resource) {
            // add new test resource
            $query = $this->aclModelBase->insert()
                ->into('acl_resource')
                ->values([
                    'resource' => $resource,
                    'module' => 1,
                    'description' => ''
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
            $resourceId = $this->aclModelBase->getAdapter()->getDriver()->getLastGeneratedValue();
            $this->aclResourcesIds[] = $resourceId;

            if ($createConnections) {
                $query = $this->aclModelBase->insert()
                    ->into('acl_resource_connection')
                    ->values([
                        'role' => $userRole,
                        'resource' => $resourceId
                    ]);

                $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
                $statement->execute();
                $this->aclResourcesConnections[] = $this->
                        aclModelBase->getAdapter()->getDriver()->getLastGeneratedValue();
            }
        }
    }

    /**
     * Init acl
     *
     * @param integer $userRole
     */
    protected function initAcl($userRole)
    {
        // init user identity
        $userIdentity = [];
        $userIdentity['role'] = $userRole;
        $userIdentity['user_id'] = $this->userId;

        UserIdentityService::setCurrentUserIdentity($userIdentity);

        // init new AclZend
        $acl = new AclZend();
        $acl->addRole(new Role($userRole));
        AclService::setCurrentAcl($acl);

        // get acl resources
        if (null != ($resources = $this->aclModelBase->
                getAclResources($userIdentity['role'], $userIdentity['user_id']))) {

            // process acl resources
            $resourcesInfo = [];
            foreach ($resources as $resource) {
                // add new resource
                $acl->addResource(new Resource($resource['resource']));

                // add resource's action
                $resource['permission'] == AclModelBase::ACTION_ALLOWED
                    ? $acl->allow($userIdentity['role'], $resource['resource'])
                    : $acl->deny($userIdentity['role'], $resource['resource']);

                $resourcesInfo[$resource['resource']] = $resource;
            }

            AclService::setCurrentAclResources($resourcesInfo);
        }
    }

    /**
     * Test acl by admin
     */
    public function testAclByAdmin()
    {
        $testResources = [
            'test application settings administration',
            'test application modules administration',
        ];

        $role = AclModelBase::DEFAULT_ROLE_ADMIN;
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertTrue(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl not exist resource
     */
    public function testAclNotExistResources()
    {
        $testResources = [
            'test application settings administration',
            'test application modules administration',
        ];

        $role = AclModelBase::DEFAULT_ROLE_MEMBER;
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertFalse(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl all resources denied globally
     */
    public function testAclAllDeniedGlobally()
    {
        $role = AclModelBase::DEFAULT_ROLE_GUEST;

        $testResources = [
            'test application settings administration'
        ];

        $this->addAclResources($testResources, true, $role);

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            // add global settings
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => new Expression('null')
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }
        
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertFalse(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl all denied globally and allowed locally
     */
    public function testAclAllDeniedGloballyAndAllowedLocally()
    {
        $role = AclModelBase::DEFAULT_ROLE_MEMBER;

        $testResources = [
            'test_application_settings_administration'
        ];

        // create test resources 
        $this->addAclResources($testResources, true, $role);

        $localActionsLimit = 1000;

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            // add global settings (all denied globally)
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => new Expression('null')
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();

            // add local settings (allowed locally)
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => $this->userId,
                    'actions_limit' => $localActionsLimit
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }

        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertTrue(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl resources local settings
     */
    public function testAclResourceLocalSettings()
    {
        $role = AclModelBase::DEFAULT_ROLE_MEMBER;

        $testResources = [
            'test_application_settings_administration'
        ];

        $this->addAclResources($testResources, true, $role);

        $globalActionsLimit = 10;
        $localActionsLimit = 1000;

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            // add global settings
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'actions_limit' => $globalActionsLimit
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();

            // add local settings
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'actions_limit' => $localActionsLimit,
                    'user_id' => $this->userId
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }

        $this->initAcl($role);

        // get registered acl resources
        $resources = AclService::getCurrentAclResources();

        // check local settings
        foreach ($testResources as $resource) {
            $this->assertEquals($localActionsLimit, $resources[$resource]['actions_limit']);
        }
    }

    /**
     * Test acl by actions with reset factor
     */
    public function testAclByActionsReset()
    {
        $role =AclModelBase::DEFAULT_ROLE_MEMBER;

        $testResources = [
            'test_application_settings_administration'
        ];

        $this->addAclResources($testResources, true, $role);
        $actionsLimit = 2; // resources be able to use only 2 times 
        $actionsReset = 1; // resource be able to use 2 times per 1 second 

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => $this->userId,
                    'actions_limit' => $actionsLimit,
                    'actions_reset' => $actionsReset
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }

        // all created acl resources must be active several times
        foreach ($testResources as $resource) {
            for ($i = 1; $i <= $actionsLimit; $i++) {
                $this->initAcl($role);
                $this->assertTrue(AclService::checkPermission($resource));
            }
        }

        // now all acl resources must be denied
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertFalse(AclService::checkPermission($resource));
        }

        sleep($actionsReset +  1);

        // all created acl resources are active again
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertTrue(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl by actions
     */
    public function testAclByActions()
    {
        $role = AclModelBase::DEFAULT_ROLE_MEMBER;

        $testResources = [
            'test_application_settings_administration',
            'test_application_modules_administration'
        ];

        $this->addAclResources($testResources, true, $role);
        $actionsLimit = 10; // resources be able to use only 10 times 

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => $this->userId,
                    'actions_limit' => $actionsLimit 
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }

        // all created acl resources must be active
        foreach ($testResources as $resource) {
            for ($i = 1; $i <= $actionsLimit; $i++) {
                $this->initAcl($role);
                $this->assertTrue(AclService::checkPermission($resource));
            }
        }

        // now all acl resource must be denied
        $this->initAcl($role);

        foreach ($testResources as $resource) {
            $this->assertFalse(AclService::checkPermission($resource));
        }
    }

    /**
     * Test acl by date
     */
    public function testAclByDate()
    {
        $role = AclModelBase::DEFAULT_ROLE_MEMBER;

        $testResources = [
            'test_application_settings_administration',
            'test_application_modules_administration'
        ];

        $this->addAclResources($testResources, true, $role);

        $currentTime = time();

        // add acl resources connections settings
        foreach ($this->aclResourcesConnections as $connectId) {
            $query = $this->aclModelBase->insert()
                ->into('acl_resource_connection_setting')
                ->values([
                    'connection_id' => $connectId,
                    'user_id' => $this->userId,
                    'date_start' => $currentTime,
                    'date_end' => $currentTime + 1,// user be able to use resource only 1 second
                ]);

            $statement = $this->aclModelBase->prepareStatementForSqlObject($query);
            $statement->execute();
        }

        $this->initAcl($role);

        // all created acl resources must be active
        foreach ($testResources as $resource) {
            $this->assertTrue(AclService::checkPermission($resource));
        }

        // wait two seconds and check acl resources again
        sleep(2);
        $this->initAcl($role);

        // now all created acl resources must be expired
        foreach ($testResources as $resource) {
            $this->assertFalse(AclService::checkPermission($resource));
        }
    }
}