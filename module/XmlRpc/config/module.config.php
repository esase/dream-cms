<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'xmlrpc' => 'XmlRpc\Controller\XmlRpcController',
        )
    ),
    'router' => array(
        'routes' => array(
            'xmlrpc' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/xmlrpc',
                    'defaults' => array(
                        'controller' => 'xmlrpc',
                        'action'     => 'index',
                    ),
                ),
            ),
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