<?php
namespace User\Test\Event;

use User\Test\UserBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Acl\Event\AclEvent;
use Zend\Db\ResultSet\ResultSet;
use Acl\Model\AclBase as AclBaseModel;

class DeleteRoleTest extends PHPUnit_Framework_TestCase
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
     * ACL roles ids
     * @var array
     */
    protected $aclRolesIds = [];

    /**
     * Users ids
     * @var array
     */
    protected $usersIds = [];

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = UserBootstrap::getServiceManager();

        // get base user model instance
        $this->model = $this->serviceManager
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