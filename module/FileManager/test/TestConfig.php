<?php

return [
    'modules' => [
        'Application',
        'Acl',
        'User',
        'Layout',
        'Localization',
        'XmlRpc',
        'FileManager'
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            APPLICATION_ROOT . 'config/autoload/{,*.}{global,local,development}.php',
        ],
        'module_paths' => [
            './module',
            './vendor',
        ],
    ],
];
