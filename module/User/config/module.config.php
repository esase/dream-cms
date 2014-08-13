<?php

return [
    'controllers' => [
        'invokables' => [
            'users-administration' => 'User\Controller\UserAdministrationController',
            'user' => 'User\Controller\UserController'
        ]
    ],
    'controller_plugins' => [
        'invokables' => [
            'userIdentity' => 'User\Controller\Plugin\UserIdentity'
        ]
    ],
    'router' => [
        'routes' => [
        ]
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ]
        ]
    ],
    'view_helpers' => [
        'invokables' => [
        ]
    ]
];