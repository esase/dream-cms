<?php

namespace FileManager\Test\Event;

use FileManager\Test\FileManagerBootstrap;
use PHPUnit_Framework_TestCase;
use Zend\Math\Rand;
use Application\Model\Acl as AclModel;
use FileManager\Model\Base as BaseFileManagerModel;
use Application\Utility\FileSystem as FileSystemUtility;
use User\Event\Event as UserEvent;

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
            ->getInstance('User\Model\Base');
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
            'role' => AclModel::DEFAULT_ROLE_MEMBER,
            'language' => null
        );

        // create a test user
        $query = $this->userModel->insert()
            ->into('user')
            ->values($data);

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();
        $testUserId = $this->userModel->getAdapter()->getDriver()->getLastGeneratedValue();

        // create a test user's home directory
        $homeUserDirectory = BaseFileManagerModel::getUserBaseFilesDir($testUserId) . '/' .
                BaseFileManagerModel::getHomeDirectoryName();

        FileSystemUtility::createDir($homeUserDirectory);

        // fire the delete user event
        UserEvent::fireUserDeleteEvent($testUserId, $data);

        // delete the created user
        $query = $this->userModel->delete()
            ->from('user')
            ->where(array('user_id' => $testUserId));

        $statement = $this->userModel->prepareStatementForSqlObject($query);
        $statement->execute();

        // home directory must be deleted
        $this->assertFalse(file_exists($homeUserDirectory));
    }
}
