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
namespace FileManager\Test\Event;

use Acl\Model\AclBase as AclModelBase;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use User\Event\UserEvent;
use FileManager\Test\FileManagerBootstrap;
use Zend\Math\Rand;
use PHPUnit_Framework_TestCase;

class DeleteUserTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $serviceLocator;

    /**
     * User model
     *
     * @var \User\Model\UserBase
     */
    protected $userModel;

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = FileManagerBootstrap::getServiceLocator();

        // get base user model instance
        $this->userModel = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\UserBase');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {}

    /**
     * Test delete user home directory
     */
    public function testDeleteUserHomeDirectory()
    {
        // test user data
        $data = [
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'api_key' => Rand::getString(32),
            'role' => AclModelBase::DEFAULT_ROLE_MEMBER,
            'language' => null
        ];

        // create a test user
        $query = $this->userModel->insert()
            ->into('user_list')
            ->values($data);

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $testUserId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user's home directory
        $homeUserDirectory = FileManagerBaseModel::getUserBaseFilesDir($testUserId) . '/' .
                FileManagerBaseModel::getHomeDirectoryName();

        FileSystemUtility::createDir($homeUserDirectory);

        // fire the delete user event
        UserEvent::fireUserDeleteEvent($testUserId, $data);

        // delete the created user
        $query = $this->userModel->delete()
            ->from('user_list')
            ->where(['user_id' => $testUserId]);

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // home directory must be deleted
        $this->assertFalse(file_exists($homeUserDirectory));
    }
}
