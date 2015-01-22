<?php

return [
    'controllers' => [
        'invokables' => [
            'pages-administration' => 'Page\Controller\PageAdministrationController',
            'page' => 'Page\Controller\PageController',
            'page-xml-sitemap' => 'Page\Controller\PageXmlSiteMapController',
            'page-robot' => 'Page\Controller\PageRobotController'
        ]
    ],
    'home_page' => 'home',
    'router' => [
        'routes' => [
            'xml_sitemap' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/sitemap.xml',
                    'defaults' => [
                        'controller' => 'page-xml-sitemap',
                        'action'     => 'index'
                    ]
                ]
            ],
            'robots' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/robots.txt',
                    'defaults' => [
                        'controller' => 'page-robot',
                        'action'     => 'index'
                    ]
                ]
            ],
            'page' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/[:language[/:page_name[/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_category/:category][/_id/:slug][/_date/:date]]][:trailing_slash]',
                    'constraints' => [
                        'language' => '[a-z]{2}',
                        'page_name' => '[0-9a-z-/]*[0-9a-z-]{1}',
                        'page' => '[0-9]+',
                        'per_page' => '[0-9]+',
                        'order_by' => '[a-z][a-z0-9-]*',
                        'category' => '[0-9a-zA-Z-]+',
                        'date'     => '[0-9]{4}/[0-9]{2}/[0-9]{2}',
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