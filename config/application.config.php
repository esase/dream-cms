<?php

define('SYSTEM_MODULES_CONFIG', __DIR__ . '/modules/system.php');
define('CUSTOM_MODULES_CONFIG', __DIR__ . '/modules/custom.php');

// define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// get list of modules
$systemModules = require_once SYSTEM_MODULES_CONFIG;
$customModules = require_once CUSTOM_MODULES_CONFIG;

return array(
    'modules' => array_merge($systemModules, $customModules),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            'config/autoload/{,*.}{global,local,' . APPLICATION_ENV . '}.php',
        ),
        'module_paths' => array(
            './module',
            './vendor',
        ),
    ),
);
