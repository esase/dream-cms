<?php

return [
    'controllers' => [
        'invokables' => [
            'pages-administration' => 'Page\Controller\PageAdministrationController',
            'page' => 'Page\Controller\PageController'
        ]
    ],
    'home_page' => 'home',
    'router' => [
        'routes' => [
            'page' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/[:language[/:page_name[/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_category/:category][/_id/:slug]]][:trailing_slash]',
                    'constraints' => [
                        'language' => '[a-z]{2}',
                        'page_name' => '[0-9a-z-/]*[0-9a-z-]{1}',
                        'page' => '[0-9]+',
                        'per_page' => '[0-9]+',
                        'order_by' => '[a-z][a-z0-9-]*',
                        'category' => '[0-9a-zA-Z-]+',
                        'slug'     => '[0-9a-zA-Z-]+',
                        'trailing_slash' => '/'
                        
                    ],
                    'defaults' => [
                        'controller' => 'Page',
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => false
            ]
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
    ],
];