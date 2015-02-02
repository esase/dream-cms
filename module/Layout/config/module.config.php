<?php

return [
    'controllers' => [
        'invokables' => [
            'layouts-administration' => 'Layout\Controller\LayoutAdministrationController'
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