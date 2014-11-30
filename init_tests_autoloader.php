<?php

namespace UnitTestBootstrap;

require_once 'init_autoloader.php';

use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;

class UnitTestBootstrap
{
    /**
     * Service locator
     * var @object
     */
    protected static $serviceLocator;

    /**
     * Config
     * var @object
     */
    protected static $config;

    /**
     * Init
     */
    public static function init()
    {
        $testConfig = require_once('TestConfig.php');

        // get modules path
        $zf2ModulePaths = array();
        if (isset($testConfig['module_listener_options']['module_paths'])) {
            $modulePaths = $testConfig['module_listener_options']['module_paths'];
            foreach ($modulePaths as $modulePath) {
                if (($path = static::findParentPath($modulePath)) ) {
                    $zf2ModulePaths[] = $path;
                }
            }
        }

        $zf2ModulePaths  = implode(PATH_SEPARATOR, $zf2ModulePaths) . PATH_SEPARATOR;

        // use ModuleManager to load this module and it's dependencies
        $baseConfig = array(
            'module_listener_options' => array(
                'module_paths' => explode(PATH_SEPARATOR, $zf2ModulePaths),
            ),
        );

        $config = ArrayUtils::merge($baseConfig, $testConfig);

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();

        static::$serviceLocator = $serviceManager;
        static::$config = $config;
    }

    /**
     * Get service locator
     *
     * @return object
     */
    public static function getServiceLocator()
    {
        return static::$serviceLocator;
    }

    /**
     * Get config
     *
     * @return object
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * Find parent path
     *
     * @return string
     */
    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';

        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) {
                return false;
            }

            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }
}