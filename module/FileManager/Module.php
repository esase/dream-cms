<?php
namespace FileManager;

use User\Event\UserEvent;
use Zend\ModuleManager\ModuleManagerInterface;

class Module
{
    /**
     * Init
     */
    public function init(ModuleManagerInterface $moduleManager)
    {
        // delete the user's files and dirs
        $eventManager = UserEvent::getEventManager();
        $eventManager->attach(UserEvent::DELETE, function ($e) use ($moduleManager) {
            // get a model instance
            $model = $moduleManager->getEvent()->getParam('ServiceManager')
                ->get('Application\Model\ModelManager')
                ->getInstance('FileManager\Model\FileManagerBase')
                ->deleteUserHomeDirectory($e->getParam('object_id'));
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
                'fileManagerDirectoriesTree' => 'FileManager\View\Helper\FileManagerDirectoryTree',
                'fileManagerFileUrl' => 'FileManager\View\Helper\FileManagerFileUrl',
                'fileManagerBaseFileUrl' => 'FileManager\View\Helper\FileManagerBaseFileUrl'
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