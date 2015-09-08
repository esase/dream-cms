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
namespace Application\Test\Model;

use Acl\Model\AclBase as AclModel;
use Application\Utility\ApplicationSlug as SlugUtility;
use Application\Test\ApplicationBootstrap;
use Zend\Math\Rand;
use Zend\Db\Sql\Predicate\In as InPredicate;
use PHPUnit_Framework_TestCase;

class ApplicationSlugTest extends PHPUnit_Framework_TestCase
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
     * @var \Application\Model\ApplicationBase
     */
    protected $model;

    /**
     * User Ids
     *
     * @var array
     */
    protected $userIds = [];

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceLocator = ApplicationBootstrap::getServiceLocator();

        // get base model instance
        $this->model = $this->serviceLocator
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationBase');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete a test user
        if ($this->userIds) {
            $query = $this->model->delete()
                ->from('user_list')
                ->where([
                    new InPredicate('user_id', $this->userIds)
                ]);

            $statement = $this->model->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->userIds = [];
        }
    }

    /**
     * Test a slug generation with similar data
     */
    public function testSimilarSlugGeneration()
    {
        // generate a first test user
        $testUserName = Rand::getString(15);
        $firstUserSlug = $testUserName;
        $firstUserId   = 1000;

        $firstUserData = [
            'user_id' => $firstUserId,
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $firstUserSlug,
            'api_key' => $firstUserId . Rand::getString(32)
        ];

        $query = $this->model->insert()
            ->into('user_list')
            ->values($firstUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate a second test user
        $secondUserSlug = '1002-' . $testUserName;
        $secondUserId   = 1001;

        $secondUserData = [
            'user_id' => $secondUserId,
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $secondUserSlug,
            'api_key' => $secondUserId . Rand::getString(32)
        ];

        $query = $this->model->insert()
            ->into('user_list')
            ->values($secondUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate slug for the third user
        $thirdUserId = 1002;
        $thirdUserSlug = $testUserName;
        $thirdUserSlug = $this->model->generateSlug($thirdUserId, $thirdUserSlug, 'user_list', 'user_id');

        $this->assertNotEquals($thirdUserSlug, $secondUserSlug);
    }

    /**
     * Test a correct slug generation
     */
    public function testCorrectSlugGeneration()
    {
        // generate a first test user
        $firstUserSlug = SlugUtility::slugify(Rand::getString(20));

        $firstUserData = [
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $firstUserSlug,
            'api_key' => Rand::getString(32)
        ];

        $query = $this->model->insert()
            ->into('user_list')
            ->values($firstUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $userId = $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate slug for the second user
        $secondUserSlug = $firstUserSlug;
        $secondUserId   = $userId + 1;

        $this->assertEquals($this->model->generateSlug($secondUserId,
                $secondUserSlug, 'user_list', 'user_id'), $secondUserId . '-' . $firstUserSlug);
    }
}
