<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'cars' => 'Cars\Controller\IndexController',
            'cars-list' => 'Cars\Controller\CarsListController',
        ),
    ),
    'router' => array(
        'routes' => array(
        ),
    ),
    'translator' => array(
        'translation_file_patterns' => array(
            array(
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
        ),
    ),
);