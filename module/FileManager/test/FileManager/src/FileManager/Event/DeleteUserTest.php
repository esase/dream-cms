<?php
namespace FileManager\Test\Event;

use FileManager\Test\FileManagerBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Acl\Model\AclBase as AclModelBase;
use FileManager\Model\FileManagerBase as FileManagerBaseModel;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use User\Event\UserEvent;

class DeleteUserTest extends PHPUnit_Framework_TestCase
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
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = FileManagerBootstrap::getServiceManager();

        // get base user model instance
        $this->userModel = $this->serviceManager
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
        $data = array(
            'nick_name' => Rand::getString(32),
            'email' => Rand::getString(32),
            'api_key' => Rand::getString(32),
            'role' => AclModelBase::DEFAULT_ROLE_MEMBER,
            'language' => null
        );

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
            ->where(array('user_id' => $testUserId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // home directory must be deleted
        $this->assertFalse(file_exists($homeUserDirectory));
    }
}
