<?php

return [
    'controllers' => [
        'invokables' => [
            'login-administration' => 'Application\Controller\ApplicationLoginAdministrationController',
            'settings-administration' => 'Application\Controller\ApplicationSettingAdministrationController',
            'error' => 'Application\Controller\ApplicationErrorController',
            'email-queue-console' => 'Application\Controller\ApplicationEmailQueueConsoleController',
            'modules-administration' => 'Application\Controller\ApplicationModuleAdministrationController'
        ]
    ],
    'router' => [
        'routes' => [
            'application' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/pages'
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'page' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/[:language[/:controller[/:action[/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_order-type/:order_type][/_category/:category][/_id/:slug][/_date/:date]]]][:trailing_slash]',
                            'constraints' => [
                                'language' => '[a-z]{2}',
                                'controller' => '[a-z][a-z0-9-]*',
                                'action' => '[a-z][a-z0-9-]*',
                                'page' => '[0-9]+',
                                'per_page' => '[0-9]+',
                                'order_by' => '[a-z][a-z0-9-]*',
                                'order_type' => 'asc|desc',
                                'category'    => '[0-9a-zA-Z-]+',
                                'slug'     => '[0-9a-zA-Z-]+',
                                'date'     => '[0-9]{4}/[0-9]{2}/[0-9]{2}',
                                'trailing_slash' => '/'
                            ],
                            'defaults' => [
                                'controller' => 'Page',
                                'action' => 'index'
                            ]
                        ],
                        'may_terminate' => true
                    ]
                ]
            ]
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'application send messages' => [
                    'options' => [
                        'route'    => 'application send messages [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'email-queue-console',
                            'action'     => 'sendMessages'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'service_manager' => [
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ],
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'applicationSetting' => 'Application\Controller\Plugin\ApplicationSetting'
        ]
    ],
    'view_manager' => [
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => []
    ]
];