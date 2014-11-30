<?php

return [
    'controllers' => [
        'invokables' => [
            'xmlrpc' => 'XmlRpc\Controller\XmlRpcController'
        ]
    ],
    'router' => [
        'routes' => [
            'xmlrpc' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/xmlrpc',
                    'defaults' => [
                        'controller' => 'xmlrpc',
                        'action'     => 'index'
                    ]
                ]
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
    ]
];