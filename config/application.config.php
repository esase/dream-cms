<?php

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

return array(
    'modules' => array(
        'Application',
        'Cars',
        'Users'
    ),
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
