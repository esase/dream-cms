<?php

namespace User;

use User\Event\Event as UserEvent;
use Zend\Mvc\MvcEvent;
use Application\Model\Acl as AclModel;
use User\Model\Base as UserBaseModel;

class Module
{
    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $eventManager = UserEvent::getEventManager();
        $eventManager->attach(UserEvent::DELETE_ACL_ROLE, function ($e) use ($mvcEvent) {
            $users = $model = $mvcEvent->getApplication()->getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\Base');

            // change the empty role with the default role
            if (null != ($usersList = $users->getUsersWithEmptyRole())) {
                foreach ($usersList as $userInfo) {
                    if (true === ($result =
                            $users->editUserRole($userInfo['user_id'], AclModel::DEFAULT_ROLE_MEMBER))) {

                        UserEvent::fireEvent(UserEvent::EDIT_ROLE,
                            $userInfo['user_id'], UserBaseModel::DEFAULT_SYSTEM_ID, 'Event - User\'s role edited by the system', array($userInfo['user_id']));
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