<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'payments-administration' => 'Payment\Controller\PaymentAdministrationController',
            'payment' => 'Payment\Controller\PaymentController'
        )
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
            'processCost' => 'Payment\View\Helper\ProcessCost',
        )
    )
);