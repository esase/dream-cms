<?php

namespace Membership;

use Application\Event\Event as ApplicationEvent;
use Membership\Event\Event as MembershipEvent;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Membership\Model\Base as BaseMembershipModel;
use User\Service\Service as UserService;
use User\Event\Event as UserEvent;
use User\Model\Base as UserBaseModel;
use Zend\ModuleManager\ModuleManagerInterface;

class Module implements ConsoleUsageProviderInterface
{
    /**
     * Init
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        $eventManager = MembershipEvent::getEventManager();

        //TODO:  Also we need to implement delete languages event for deleting all membership levels with empty language 

        // listen the edit user role event
        $eventManager->attach(UserEvent::EDIT_ROLE, function ($e) use ($moduleManager) {
            // someone forced set a user's role, and now we must clean all the user's membership queue
            if ($e->getParam('user_id') != UserBaseModel::DEFAULT_SYSTEM_ID) {
                // get the model manager instance
                $model = $moduleManager->getEvent()
                    ->getParam('ServiceManager')
                    ->get('Application\Model\ModelManager')
                    ->getInstance('Membership\Model\Base');

                // delete all connections
                if (null != ($connections = $model->getAllUserMembershipConnections(($e->getParam('object_id'))))) {
                    foreach ($connections as $connection) {
                        // delete the connection
                        if (false === ($deleteResult = $model->deleteMembershipConnection($connection->id))) {
                            break;
                        }
                    }
                }
            }
        });

        // listen the delete acl event
        $eventManager->attach(ApplicationEvent::DELETE_ACL_ROLE, function ($e) use ($moduleManager) {
            // get the model manager instance
            $modelManager = $moduleManager->getEvent()
                ->getParam('ServiceManager')
                ->get('Application\Model\ModelManager');

            $model = $modelManager->getInstance('Membership\Model\Base');

            // delete connected membership levels
            if (null != ($membershipLevels = $model->getAllMembershipLevels($e->getParam('object_id')))) {
                foreach ($membershipLevels as $levelInfo) {
                    $model->deleteRole($levelInfo, true);
                }

                // synchronize users membership levels
                if (null != ($membershipLevels  = $model->getUsersMembershipLevels())) {
                    $userModel = $modelManager->getInstance('User\Model\Base');

                    // process membership levels
                    foreach ($membershipLevels as $levelInfo) {
                        // set the next membership level
                        if ($levelInfo['active'] != BaseMembershipModel::MEMBERSHIP_LEVEL_CONNECTION_ACTIVE) {
                            // change the user's role 
                            $userModel->editUserRole($levelInfo['user_id'], 
                                    $levelInfo['role_id'], $levelInfo['role_name'], $levelInfo, true);

                            // activate the next membership connection
                            $model->activateMembershipConnection($levelInfo['connection_id']);
                        }
                    }
                }
            }
        });
    }

    /**
     * Return autoloader config array
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Return service config array
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
        );
    }

    /**
     * Init view helpers
     */
    public function getViewHelperConfig()
    {
        return array(
            'invokables' => array(
                'membershipImageUrl' => 'Membership\View\Helper\MembershipImageUrl'
            ),
        );
    }

    /**
     * Return path to config file
     *
     * @return boolean
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get console usage info
     *
     * @param object $console
     * @return array
     */
    public function getConsoleUsage(Console $console)
    {
        return array(
            // describe available commands
            'membership clean expired connections [--verbose|-v]' => 'Clean expired membership connections',
            // describe expected parameters
            array('--verbose|-v', '(optional) turn on verbose mode'),
        );
    }
}