<?php

return [
    'modules' => [
        'Install'
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