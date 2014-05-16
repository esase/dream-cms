<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'payments-administration' => 'Payment\Controller\PaymentAdministrationController',
            'payments' => 'Payment\Controller\PaymentController',
            'payments-console' => 'Payment\Controller\PaymentConsoleController'
        )
    ),
    'router' => array(
        'routes' => array(
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'payments clean' => array(
                    'options' => array(
                        'route'    => 'payment clean expired items [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'payments-console',
                            'action'     => 'cleanExpiredItems'
                        )
                    )
                )
            )
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
    )
);