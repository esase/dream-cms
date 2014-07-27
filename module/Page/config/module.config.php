<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'page' => 'Page\Controller\PageController',
        )
    ),
    'router' => array(
        'routes' => array(
            'page' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/[:languge[/:page_name[[/]_page/:page][[/]_per-page/:per_page][[/]_order-by/:order_by][[/]_id/:slug]]][/]',
                    'constraints' => array(
                        'languge' => '[a-z]{2}',
                        'page_name' => '[0-9a-z-/]*',
                        'page' => '[0-9]+',
                        'per_page' => '[0-9]+',
                        'order_by' => '[a-z][a-z0-9-]*',
                        'slug'     => '[0-9a-zA-Z-_]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Page',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => false
            ),
            /*'application' => array(
                'type'    => 'segment',
                'options' => array(
                    'priority' => 1,
                    'route'    => '/[:languge[/:controller[/:action[/page/:page][/per-page/:per_page][/order-by/:order_by][/order-type/:order_type][/:slug][/:extra]]]][/]',
                    'constraints' => array(
                        'languge' => '[a-z]{2}',
                        'controller' => '[a-z][a-z0-9-]*',
                        'action' => '[a-z][a-z0-9-]*',
                        'page' => '[0-9]+',
                        'per_page' => '[0-9]+',
                        'order_by' => '[a-z][a-z0-9-]*',
                        'order_type' => 'asc|desc',
                        'slug'     => '[0-9a-zA-Z-_]+',
                        'extra'    => '[0-9a-zA-Z-_]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Page',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => false
            )*/
        )
    ),
    'translator' => array(
        'translation_file_patterns' => array(
            array(
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            )
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
        )
    )
);