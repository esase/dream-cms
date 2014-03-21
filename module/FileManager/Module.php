<?php

namespace FileManager;

use Zend\Mvc\MvcEvent;
use User\Event\Event as UserEvent;
use FileManager\Event\Event as FileManagerEvent;
use Application\Utility\ErrorLogger;
use User\Model\Base as UserBaseModel;

class Module
{
    /**
     * Bootstrap
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        // delete the user's files and dirs
        $eventManager = FileManagerEvent::getEventManager();
        $eventManager->attach(UserEvent::DELETE, function ($e) use ($mvcEvent) {

            $model = $mvcEvent->getApplication()->getServiceManager()
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\Base');

            if (false === ($fullPath
                    = $model->deleteUserHomeDirectory($e->getParam('object_id')))) {

                ErrorLogger::log('Cannot delete files and directories for user id: ' . $e->getParam('object_id'));
            }
            else if (null != $fullPath) {
                // fire the event
                FileManagerEvent::fireEvent(FileManagerEvent::DELETE_DIRECTORY,
                        $fullPath, UserBaseModel::DEFAULT_SYSTEM_ID, 'Event - Directory deleted by the system', array($fullPath));
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