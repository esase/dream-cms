<?php

namespace Application\Test\Controller;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Application\Model\Acl as AclModel;
use Application\Utility\Slug as SlugUtility;
use Zend\Db\Sql\Predicate\In as InPredicate;

class SlugTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * User Ids
     * @var array
     */
    protected $userIds = array();

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = ApplicationBootstrap::getServiceManager();

        // get base model instance
        $this->model = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\Base');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete a test user
        if ($this->userIds) {
            $query = $this->model->delete()
                ->from('user')
                ->where(array(
                    new InPredicate('user_id', $this->userIds)
                ));

            $statement = $this->model->prepareStatementForSqlObject($query);
            $statement->execute();
            $this->userIds = array();
        }
    }

    /**
     * Test a slug generation with similar data
     */
    public function testSimilarSlugGeneration()
    {
        // generate a first test user
        $firstUserSlug = 'terminator';
        $firstUserId   = 1000;

        $firstUserData = array(
            'user_id' => $firstUserId,
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $firstUserSlug,
            'api_key' => $firstUserId . Rand::getString(32)
        );

        $query = $this->model->insert()
            ->into('user')
            ->values($firstUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate a second test user
        $secondUserSlug = '1002-terminator';
        $secondUserId   = 1001;

        $secondUserData = array(
            'user_id' => $secondUserId,
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $secondUserSlug,
            'api_key' => $secondUserId . Rand::getString(32)
        );

        $query = $this->model->insert()
            ->into('user')
            ->values($secondUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate slug for the third user
        $thirdUserId = 1002;
        $thirdUserSlug = 'terminator';
        $thirdUserSlug = $this->model->generateSlug($thirdUserId, $thirdUserSlug, 'user', 'user_id');

        $this->assertNotEquals($thirdUserSlug, $secondUserSlug);
    }

    /**
     * Test a correct slug generation
     */
    public function testCorrectSlugGeneration()
    {
        // generate a first test user
        $firstUserSlug = SlugUtility::slugify(Rand::getString(20));

        $firstUserData = array(
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'slug' => $firstUserSlug,
            'api_key' => Rand::getString(32)
        );

        $query = $this->model->insert()
            ->into('user')
            ->values($firstUserData);

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $userId = $this->userIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // generate slug for the second user
        $secondUserSlug = $firstUserSlug;
        $secondUserId   = $userId + 1;

        $this->assertEquals($this->model->generateSlug($secondUserId,
                $secondUserSlug, 'user', 'user_id'), $secondUserId . '-' . $firstUserSlug);
    }
}
