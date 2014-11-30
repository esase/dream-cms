<?php
namespace Application\Test\Model;

use Application\Test\ApplicationBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Acl\Model\AclBase as AclModel;
use Application\Utility\ApplicationSlug as SlugUtility;
use Zend\Db\Sql\Predicate\In as InPredicate;

class ApplicationSlugTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * User Ids
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
        $firstUserSlug = 'terminator';
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
        $secondUserSlug = '1002-terminator';
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
        $thirdUserSlug = 'terminator';
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
