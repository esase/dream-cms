<?php
namespace User\Test\Event;

use User\Test\UserBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Application\Event\Event as ApplicationEvent;
use Zend\Db\ResultSet\ResultSet;
use Application\Model\Acl as AclModel;

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
    protected $aclRolesIds = array();

    /**
     * Users ids
     * @var array
     */
    protected $usersIds = array();

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
            ->getInstance('User\Model\Base');
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
                    ->from('application_acl_role')
                    ->where(array('id' => $roleId));

                $statement = $this->model->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->aclRolesIds = array();
        }

        // delete test users
        if ($this->usersIds) {
            foreach ($this->usersIds as $userId) {
                $query = $this->model->delete()
                    ->from('user_list')
                    ->where(array('user_id' => $userId));

                $statement = $this->model->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->usersIds = array();
        }
    }

    /**
     * Test role synchronisation
     */
    public function testRoleSynchronisation()
    {
        // create a first test ACL role
        $query = $this->model->insert()
            ->into('application_acl_role')
            ->values(array(
                'name' => 'test role 1'
            ));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user
        $query = $this->model->insert()
            ->into('user_list')
            ->values(array(
                'nick_name' => Rand::getString(32),
                'email' => Rand::getString(32),
                'role' => $this->aclRolesIds[0]
            ));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->usersIds[] = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        // delete the created ACL role
        $query = $this->model->delete()
            ->from('application_acl_role')
            ->where(array('id' => $this->aclRolesIds[0]));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        ApplicationEvent::fireDeleteAclRoleEvent($this->aclRolesIds[0]);

        // check the created test user's role
        $select = $this->model->select();
        $select->from('user_list')
            ->columns(array(
                'role'
            ))
            ->where(array(
                'user_id' => $this->usersIds[0]
            ));

        $statement = $this->model->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        // user must be a default member
        $this->assertEquals($result->current()['role'], AclModel::DEFAULT_ROLE_MEMBER);
    }
}