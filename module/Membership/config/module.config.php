<?php

return array (
    'controllers' => array(
        'invokables' => array(
            'memberships-administration' => 'Membership\Controller\MembershipAdministrationController',
            'memberships-console' => 'Membership\Controller\MembershipConsoleController'
        )
    ),
    'router' => array(
        'routes' => array(
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'membership clean connections' => array(
                    'options' => array(
                        'route'    => 'membership clean expired connections [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'memberships-console',
                            'action'     => 'cleanExpiredMembershipsConnections'
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
        'invokables' => array(
        )
    )
);