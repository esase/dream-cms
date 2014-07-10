<?php
namespace Membership\Test;

use PHPUnit_Framework_TestCase;
use Membership\Test\MembershipBootstrap;

abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * User model
     * @var object
     */
    protected $userModel;

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
     * Membership levels ids
     * @var array
     */
    protected $membershipLevelsIds = array();

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = MembershipBootstrap::getServiceManager();

        // get base user model instance
        $this->userModel = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('User\Model\Base');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete test users
        if ($this->usersIds) {
            foreach ($this->usersIds as $userId) {
                $query = $this->userModel->delete()
                    ->from('user_list')
                    ->where(array('user_id' => $userId));

                $statement = $this->userModel->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->usersIds = array();
        }

        // delete test ACL roles
        if ($this->aclRolesIds) {
            foreach ($this->aclRolesIds as $roleId) {
                $query = $this->userModel->delete()
                    ->from('application_acl_role')
                    ->where(array('id' => $roleId));

                $statement = $this->userModel->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->aclRolesIds = array();
        }

        // delete test membership levels
        if ($this->membershipLevelsIds) {
            foreach ($this->membershipLevelsIds as $levelId) {
                $query = $this->userModel->delete()
                    ->from('membership_level')
                    ->where(array('id' => $levelId));

                $statement = $this->userModel->prepareStatementForSqlObject($query);
                $statement->execute();
            }

            $this->membershipLevelsIds = array();
        }
    }
}