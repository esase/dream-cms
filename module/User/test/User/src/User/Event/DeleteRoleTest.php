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
namespace User\Test\Event;

use Acl\Model\AclBase as AclBaseModel;
use Acl\Event\AclEvent;
use User\Test\UserBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Zend\Db\ResultSet\ResultSet;

class DeleteRoleTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * Model
     *
     * @var \Acl\Model\AclBase
     */
    protected $model;

    /**
     * ACL roles ids
     *
     * @var array
     */
    protected $aclRolesIds = [];

    /**
     * Users ids
     *
     * @var array
     */
    protected $usersIds = [];

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = UserBootstrap::getServiceLocator();

        // get base user model instance
        $this->model = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\UserBase');
    }

     /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete test ACL roles
        if ($this->aclRolesIds) {
            foreach ($this->aclRolesIds as $roleId) {
                $query = $this->model->delete()
                    ->from('acl_role')
                    ->where(['id' => $roleId]);

                $statement = $this->model->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->aclRolesIds = [];
        }

        // delete test users
        if ($this->usersIds) {
            foreach ($this->usersIds as $userId) {
                $query = $this->model->delete()
                    ->from('user_list')
                    ->where(['user_id' => $userId]);

                $statement = $this->model->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->usersIds = [];
        }
    }

    /**
     * Test role synchronisation
     */
    public function testRoleSynchronisation()
    {
        // create a first test ACL role
        $query = $this->model->insert()
            ->into('acl_role')
            ->values([
                'name' => 'test role 1'
            ]);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user
        $query = $this->model->insert()
            ->into('user_list')
            ->values([
                'nick_name' => Rand::getString(32),
                'email' => Rand::getString(32),
                'role' => $this->aclRolesIds[0]
            ]);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->usersIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // delete the created ACL role
        $query = $this->model->delete()
            ->from('acl_role')
            ->where(['id' => $this->aclRolesIds[0]]);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        AclEvent::fireDeleteAclRoleEvent($this->aclRolesIds[0]);

        // check the created test user's role
        $select = $this->model->select();
        $select->from('user_list')
            ->columns([
                'role'
            ])
            ->where([
                'user_id' => $this->usersIds[0]
            ]);

        $statement = $this->model->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        // user must be a default member
        $this->assertEquals($result->current()['role'], AclBaseModel::DEFAULT_ROLE_MEMBER);
    }
}