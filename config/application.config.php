<?php

use Zend\Stdlib\ArrayUtils;

$applicationConfigCache = APPLICATION_ROOT . '/data/cache/config';
$isConfigDirWritable = is_writable($applicationConfigCache);

define('SYSTEM_MODULES_CONFIG', __DIR__ . '/module/system.php');
define('CUSTOM_MODULES_CONFIG', __DIR__ . '/module/custom.php');

// define the application environment
if (!defined('APPLICATION_ENV')) {
    php_sapi_name() == 'cli'
        ? define('APPLICATION_ENV', 'console')
        : define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
}

// get list of modules
$systemModules = require_once SYSTEM_MODULES_CONFIG;
$customModules = require_once CUSTOM_MODULES_CONFIG;
$extraConfig   = require_once 'application.config.' . APPLICATION_ENV. '.php';

return ArrayUtils::merge([
    'modules' => array_merge($systemModules, $customModules),
    'module_listener_options' => [
        'config_glob_paths'    => [
            'config/autoload/{,*.}{global,local,' . APPLICATION_ENV . '}.php',
        ],
        'module_paths' => [
            './module',
            './vendor',
        ]
    ],
], $extraConfig);