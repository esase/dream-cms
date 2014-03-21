<?php

use Zend\Mvc\Controller\ControllerManager;

return array(
    'controllers' => array(
        'invokables' => array(
            'files-manager-embedded' => 'FileManager\Controller\FileManagerEmbeddedController',
            'files-manager-administration' => 'FileManager\Controller\FileManagerAdministrationController'
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
            )
        )
    ),
    'view_helpers' => array(
        'invokables' => array(
        )
    )
);