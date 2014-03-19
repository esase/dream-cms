<?php

namespace FileManager;

use Zend\Mvc\MvcEvent;
use User\Event\Event as UserEvent;
use Application\Utility\ErrorLogger;

class Module
{
    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $eventManager = UserEvent::getEventManager();
        $eventManager->attach(UserEvent::USER_DELETE, function ($e) use ($mvcEvent) {
            // delete user's directories and files
            $model = $mvcEvent->getApplication()->getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\Base');

            if (false === ($result = $model->
                    deleteUserHomeDirectory($e->getParam('object_id')))) {

                ErrorLogger::log('Cannot delete files and directories for user id: ' . $e->getParam('object_id'));
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
                'filesManagerDirectoriesTree' => 'FileManager\View\Helper\FileManagerDirectoryTree',
                'fileUrl' => 'FileManager\View\Helper\FileUrl',
                'baseFileUrl' => 'FileManager\View\Helper\BaseFileUrl'
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