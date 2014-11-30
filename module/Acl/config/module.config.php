<?php

return [
    'controllers' => [
        'invokables' => [
            'acl-administration' => 'Acl\Controller\AclAdministrationController'
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
    'controller_plugins' => [
        'invokables' => [
            'aclCheckPermission' => 'Acl\Controller\Plugin\AclCheckPermission'
        ]
    ],
    'view_helpers' => [
        'invokables' => [
        ]
    ]
];