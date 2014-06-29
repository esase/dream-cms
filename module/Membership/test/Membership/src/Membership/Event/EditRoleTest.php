<?php
namespace Membership\Test\Event;

use Zend\Math\Rand;
use Zend\Db\ResultSet\ResultSet;
use Membership\Model\Base as BaseMembershipModel;
use Application\Model\Acl as AclBaseModel;
use User\Event\Event as UserEvent;
use Membership\Test\BaseTest;

class EditRoleTest extends BaseTest
{
    protected $testUsersData = array();

    /**
     * Create test resources
     * 
     * @return integer - membership connection id
     */
    protected function createTestResources()
    {
        // create a test ACL role
        $query = $this->userModel->insert()
            ->into('acl_role')
            ->values(array(
                'name' => 'test role 1'
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test membership level
        $query = $this->userModel->insert()
            ->into('membership_level')
            ->values(array(
                'role_id' => $this->aclRolesIds[0]
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->membershipLevelsIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user
        $userData = array(
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'role' => $this->aclRolesIds[0],
            'language' => null
        );

        $query = $this->userModel->insert()
            ->into('user')
            ->values($userData);

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->usersIds[] = $userId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();
        $this->testUsersData[$userId] = $userData;

        // create an active test membership level connection
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $this->usersIds[0],
                'membership_id' => $this->membershipLevelsIds[0],
                'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        return $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();
    }

    /**
     * Test edit an ACL role by the system (membership connection queue must not be touched)
     */
    public function testEditAclRoleBySystem()
    {
        $membershipConnectionId = $this->createTestResources();

        // change the user's role (now he is a member)
        if (true === ($result = 
                $this->userModel->editUserRole($this->usersIds[0], AclBaseModel::DEFAULT_ROLE_MEMBER))) {

            // fire the edit user role event
            UserEvent::fireEditRoleEvent(array_merge($this->testUsersData[$this->usersIds[0]], array('user_id' => 
                    $this->usersIds[0])), AclBaseModel::DEFAULT_ROLE_MEMBER_NAME, true);
        }

        // membership connection queue must not be empty for specific user
        $select = $this->userModel->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'id'
            ))
            ->where(array(
                'id' => $membershipConnectionId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());
        $connectionId = $result->current();

        $this->assertTrue($connectionId ? true : false);
    }

    /**
     * Test edit an ACL role by a user (membership connection queue must be cleared)
     */
    public function testEditAclRoleByUser()
    {
        $membershipConnectionId = $this->createTestResources();

        // change the user's role (now he is a member)
        if (true === ($result = 
                $this->userModel->editUserRole($this->usersIds[0], AclBaseModel::DEFAULT_ROLE_MEMBER))) {

            // fire the edit user role event
            UserEvent::fireEditRoleEvent(array_merge($this->testUsersData[$this->usersIds[0]], array('user_id' => 
                    $this->usersIds[0])), AclBaseModel::DEFAULT_ROLE_MEMBER_NAME);
        }

        // now all membership connection queue must be empty for specific user
        $select = $this->userModel->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'id'
            ))
            ->where(array(
                'id' => $membershipConnectionId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());
        $connectionId = $result->current();

        $this->assertFalse($connectionId ? true : false);
    }
}
