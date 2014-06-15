<?php

namespace Membership\Test\Event;

use Membership\Model\Localization;
use Zend\Math\Rand;
use Membership\Model\Base as BaseMembershipModel;
use Application\Event\Event as ApplicationEvent;
use Zend\Db\ResultSet\ResultSet;
use Application\Model\Acl as AclModel;
use Membership\Test\BaseTest;

class DeleteRoleTest extends BaseTest
{
    /**
     * Create test resources
     * 
     * @return void
     */
    protected function createTestResources()
    {
        // create a first test ACL role
        $query = $this->userModel->insert()
            ->into('acl_role')
            ->values(array(
                'name' => 'test role 1'
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a second test ACL role
        $query = $this->userModel->insert()
            ->into('acl_role')
            ->values(array(
                'name' => 'test role 2'
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a third test ACL role
        $query = $this->userModel->insert()
            ->into('acl_role')
            ->values(array(
                'name' => 'test role 3'
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->aclRolesIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user
        $query = $this->userModel->insert()
            ->into('user')
            ->values(array(
                'nick_name' => Rand::getString(32),
                'email' => Rand::getString(32),
                'role' => $this->aclRolesIds[0]
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->usersIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a first test membership level
        $query = $this->userModel->insert()
            ->into('membership_level')
            ->values(array(
                'role_id' => $this->aclRolesIds[0]
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->membershipLevelsIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a second test membership level
        $query = $this->userModel->insert()
            ->into('membership_level')
            ->values(array(
                'role_id' => $this->aclRolesIds[1]
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->membershipLevelsIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a third test membership level
        $query = $this->userModel->insert()
            ->into('membership_level')
            ->values(array(
                'role_id' => $this->aclRolesIds[2]
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->membershipLevelsIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();
    }

    /**
     * Test active membership connection
     */
    public function testActiveMembershipConnection()
    {
        $this->createTestResources();

        $testUserId = current($this->usersIds);
        list($firstRoleId, $secondRoleId, $thirdRoleId) = $this->aclRolesIds;
        list($firstMembershipLevelId, $secondMembershipLevelId, $thirdMembershipLevelId) = $this->membershipLevelsIds;

        // create an active test membership level connection
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $testUserId,
                'membership_id' => $firstMembershipLevelId,
                'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $activeMembershipConnectionId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // delete the second created ACL role
        $query = $this->userModel->delete()
            ->from('acl_role')
            ->where(array('id' => $secondRoleId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        ApplicationEvent::fireDeleteAclRoleEvent($secondRoleId);

        // delete the third created ACL role
        $query = $this->userModel->delete()
            ->from('acl_role')
            ->where(array('id' => $thirdRoleId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        ApplicationEvent::fireDeleteAclRoleEvent($thirdRoleId);

        // check the status of second membership level connection
        $select = $this->userModel->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'active'
            ))
            ->where(array(
                'id' => $activeMembershipConnectionId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        $this->assertEquals($result->current()['active'], BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE);
    }

    /**
     * Test membership connection empty queue
     */
    public function testMembershipConnectionEmptyQueue()
    {
        $this->createTestResources();

        $testUserId = current($this->usersIds);
        list($firstRoleId, $secondRoleId, $thirdRoleId) = $this->aclRolesIds;
        list($firstMembershipLevelId, $secondMembershipLevelId, $thirdMembershipLevelId) = $this->membershipLevelsIds;

        // create a test membership level connection
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $testUserId,
                'membership_id' => $firstMembershipLevelId,
                'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $membershipConnectionId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // delete the first created ACL role
        $query = $this->userModel->delete()
            ->from('acl_role')
            ->where(array('id' => $firstRoleId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        ApplicationEvent::fireDeleteAclRoleEvent($firstRoleId);

        // check the status of second membership level connection
        $select = $this->userModel->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'active'
            ))
            ->where(array(
                'id' => $membershipConnectionId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        $this->assertEquals(count($result), 0);

        // check the created test user's role
        $select = $this->userModel->select();
        $select->from('user')
            ->columns(array(
                'role'
            ))
            ->where(array(
                'user_id' => $testUserId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());
        
        // user must be a default member
        $this->assertEquals($result->current()['role'], AclModel::DEFAULT_ROLE_MEMBER);
    }

    /**
     * Test membership connection queue
     */
    public function testMembershipConnectionQueue()
    {
        $this->createTestResources();

        $testUserId = current($this->usersIds);
        list($firstRoleId, $secondRoleId, $thirdRoleId) = $this->aclRolesIds;
        list($firstMembershipLevelId, $secondMembershipLevelId, $thirdMembershipLevelId) = $this->membershipLevelsIds;
        
        // create a first test membership level connection
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $testUserId,
                'membership_id' => $firstMembershipLevelId,
                'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $firstMembershipConnectionId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a second test membership level connection
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $testUserId,
                'membership_id' => $secondMembershipLevelId,
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $secondMembershipConnectionId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // delete the first created ACL role
        $query = $this->userModel->delete()
            ->from('acl_role')
            ->where(array('id' => $firstRoleId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // fire the delete ACL role event
        ApplicationEvent::fireDeleteAclRoleEvent($firstRoleId);

        // check the status of second membership level connection
        $select = $this->userModel->select();
        $select->from('membership_level_connection')
            ->columns(array(
                'active'
            ))
            ->where(array(
                'id' => $secondMembershipConnectionId
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $result = $resultSet->initialize($statement->execute());

        $this->assertEquals($result->current()['active'], BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE);
    }
}
