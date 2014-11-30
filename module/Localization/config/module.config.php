<?php

return [
    'controllers' => [
        'invokables' => [
        ]
    ],
    'controller_plugins' => [
        'invokables' => [
            'localization' => 'Localization\Controller\Plugin\Localization'
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