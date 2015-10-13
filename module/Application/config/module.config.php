<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
return [
    'controllers' => [
        'invokables' => [
            'login-administration' => 'Application\Controller\ApplicationLoginAdministrationController',
            'settings-administration' => 'Application\Controller\ApplicationSettingAdministrationController',
            'error' => 'Application\Controller\ApplicationErrorController',
            'email-queue-console' => 'Application\Controller\ApplicationEmailQueueConsoleController',
            'delete-content-console' => 'Application\Controller\ApplicationDeleteContentConsoleController',
            'modules-administration' => 'Application\Controller\ApplicationModuleAdministrationController'
        ]
    ],
    'router' => [
        'routes' => [
            'application' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/pages'
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'page' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/[:language[/:controller[/:action[/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_order-type/:order_type][/_category/:category][/_id/:slug][/_date/:date]]]][:trailing_slash]',
                            'constraints' => [
                                'language' => '[a-z]{2}',
                                'controller' => '[a-z][a-z0-9-]*',
                                'action' => '[a-z][a-z0-9-]*',
                                'page' => '[0-9]+',
                                'per_page' => '[0-9]+',
                                'order_by' => '[a-z][a-z0-9-]*',
                                'order_type' => 'asc|desc',
                                'category'    => '[0-9a-zA-Z-]+',
                                'slug'     => '[0-9a-zA-Z-]+',
                                'date'     => '[0-9]{4}/[0-9]{2}/[0-9]{2}',
                                'trailing_slash' => '/'
                            ],
                            'defaults' => [
                                'controller' => 'Page',
                                'action' => 'index'
                            ]
                        ],
                        'may_terminate' => true
                    ]
                ]
            ]
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'application send messages' => [
                    'options' => [
                        'route'    => 'application send messages [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'email-queue-console',
                            'action'     => 'sendMessages'
                        ]
                    ]
                ],
                'application delete content' => [
                    'options' => [
                        'route'    => 'application delete content [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'delete-content-console',
                            'action'     => 'deleteContent'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'service_manager' => [
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'     => 'getText',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
                'text_domain'  => 'default'
            ],
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'applicationSetting' => 'Application\Controller\Plugin\ApplicationSetting',
            'applicationCsrf' => 'Application\Controller\Plugin\ApplicationCsrf'
        ]
    ],
    'view_manager' => [
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => []
    ]
];