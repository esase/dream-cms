<?php

use Zend\Mvc\Controller\ControllerManager;

return [
    'controllers' => [
        'invokables' => [
            'files-manager-embedded' => 'FileManager\Controller\FileManagerEmbeddedController',
            'files-manager-administration' => 'FileManager\Controller\FileManagerAdministrationController'
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