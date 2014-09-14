<?php

return array(
    'modules' => array(
        'Application',
        'Acl',
        'User',
        'Layout',
        'Localization',
        'Page'
    ),
    'module_listener_options' => array(
        'config_glob_paths' => array(
            APPLICATION_ROOT . 'config/autoload/{,*.}{global,local,development}.php',
        ),
        'module_paths' => array(
            './module',
            './vendor',
        ),
    ),
);
