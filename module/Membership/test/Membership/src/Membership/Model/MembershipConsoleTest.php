<?php
namespace Membership\Test\Model;

use Zend\Math\Rand;
use Membership\Test\BaseTest;
use Membership\Model\Base as BaseMembershipModel;

class MembershipConsoleTest extends BaseTest
{
    /**
     * Create test resources
     * 
     * @return integer - membership connection id
     */
    protected function createTestResources()
    {
        // create a test ACL role
        $query = $this->userModel->insert()
            ->into('application_acl_role')
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
            ->into('user_list')
            ->values($userData);

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->usersIds[] = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create an expired membership connection
        $time = time();
        $query = $this->userModel->insert()
            ->into('membership_level_connection')
            ->values(array(
                'user_id' => $this->usersIds[0],
                'membership_id' => $this->membershipLevelsIds[0],
                'active' => BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE,
                'expire_date' => $time,
                'notify_date' => $time
            ));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        return $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();
    }

    /**
     * Test expired memberships connections
     */
    public function testExpiredMembershipsConnections()
    {
        $connectionId = $this->createTestResources();

        // get a membership  model instance
        $membershipModel = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Membership\Model\MembershipConsole');

        // check the value
        $expiredConnectionId = 0;
        $data = $membershipModel->getExpiredMembershipsConnections();

        if (count($data)) {
            $expiredConnectionId = current($data)['id'];
        }

        $this->assertEquals($connectionId, $expiredConnectionId);
    }

    /**
     * Test memberships connections notification 
     */
    public function testMembershipsConnectionsNotification()
    {
        $connectionId = $this->createTestResources();

        // get a membership  model instance
        $membershipModel = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Membership\Model\MembershipConsole');

        // check the value
        $notifiedConnectionId = 0;
        $data = $membershipModel->getNotNotifiedMembershipsConnections();

        if ($data->count()) {
            $notifiedConnectionId = $data->current()->id;
        }

        $this->assertEquals($connectionId, $notifiedConnectionId);
    }
}
