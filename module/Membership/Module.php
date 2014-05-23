<?php

namespace Membership;

use Membership\Event\Event as MembershipEvent;
use Zend\Mvc\MvcEvent;
use User\Model\Base as UserBaseModel;

class Module
{
    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $eventManager = MembershipEvent::getEventManager();
        $eventManager->attach(MembershipEvent::DELETE_ACL_ROLE, function ($e) use ($mvcEvent) {
            $model = $mvcEvent->getApplication()->getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('Membership\Model\Base');

            // delete connected membership levels
            if (null != ($membershipLevels = $model->getAllMembershipLevels($e->getParam('object_id')))) {
                foreach ($membershipLevels as $levelInfo) {
                    if (true === ($result = $model->deleteRole($levelInfo))) {
                        MembershipEvent::fireEvent(MembershipEvent::DELETE_MEMBERSHIP_ROLE, $e->getParam('object_id'), 
                                UserBaseModel::DEFAULT_SYSTEM_ID, 'Event - Membership role deleted by the system', array($e->getParam('object_id')));
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