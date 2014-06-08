<?php

namespace User;

use User\Event\Event as UserEvent;
use Application\Event\Event as ApplicationEvent;
use Application\Model\Acl as AclModel;
use Zend\ModuleManager\ModuleManager;

class Module
{
    /**
     * Init
     */
    public function init(ModuleManager $moduleManager)
    {
        $eventManager = UserEvent::getEventManager();
        $eventManager->attach(ApplicationEvent::DELETE_ACL_ROLE, function ($e) use ($moduleManager) {
            $users = $moduleManager->getEvent()->getParam('ServiceManager')
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\Base');

            // change the empty role with the default role
            if (null != ($usersList = $users->getUsersWithEmptyRole())) {
                // process users list
                foreach ($usersList as $userInfo) {
                    if (true === ($result = 
                            $users->editUserRole($userInfo['user_id'], AclModel::DEFAULT_ROLE_MEMBER))) {

                        // fire the edit user role event
                        UserEvent::fireEditRoleEvent($userInfo, AclModel::DEFAULT_ROLE_MEMBER_NAME, true);
                    }
                }
            }
        }, -100);
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
                'userAvatarUrl' => 'User\View\Helper\UserAvatarUrl'
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
}