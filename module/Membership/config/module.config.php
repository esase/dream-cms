<?php

return array (
    'controllers' => array(
        'invokables' => array(
            'memberships-administration' => 'Membership\Controller\MembershipAdministrationController',
        )
    ),
    'router' => array(
        'routes' => array(
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