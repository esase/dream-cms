<?php

return [
    'cms_name' => 'Dream CMS',
    'cms_version' => '2.0.0',
    'install_languages' => [
        'ru' => [
            'language' => 'ru',
            'locale' => 'ru_RU',
            'description' => 'Русский',
            'default' => false,
            'direction' => 'ltr'
        ],
        'en' => [
            'language' => 'en',
            'locale' => 'en_US',
            'description' => 'English',
            'default' => true,
            'direction' => 'ltr'
        ]
    ],
    'controllers' => [
        'invokables' => [   
            'Install\Controller\Index' => 'Install\Controller\IndexController'
        ]
    ],
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Install\Controller\Index',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'page' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '[:language][:trailing_slash]',
                            'constraints' => [
                                'language' => '[a-z]{2}',
                                'trailing_slash' => '/'
                            ],
                        ]
                    ]
                ]
            ],
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
                            'route'    => '/[:language[/:controller[/:action[/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_order-type/:order_type][/_category/:category][/_id/:slug]]]][:trailing_slash]',
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
            ]
        ]
    ],
    'view_helpers' => [
        'invokables' => [
        ]
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'install/index/index' => __DIR__ . '/../view/install/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => [
            __DIR__ . '/../view',
        ]
    ]
];