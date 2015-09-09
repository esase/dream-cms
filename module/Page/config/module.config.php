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
            'pages-administration' => 'Page\Controller\PageAdministrationController',
            'pages-ajax' => 'Page\Controller\PageAjaxController',
            'page' => 'Page\Controller\PageController',
            'page-xml-sitemap' => 'Page\Controller\PageXmlSiteMapController',
            'page-robot' => 'Page\Controller\PageRobotController'
        ]
    ],
    'home_page' => 'home',
    'router' => [
        'routes' => [
            'xml_sitemap' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/sitemap.xml',
                    'defaults' => [
                        'controller' => 'page-xml-sitemap',
                        'action'     => 'index'
                    ]
                ]
            ],
            'robots' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/robots.txt',
                    'defaults' => [
                        'controller' => 'page-robot',
                        'action'     => 'index'
                    ]
                ]
            ],
            'page' => [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/[:language[/:page_name[/_category/:category][/_date/:date][/_id/:slug][/_page/:page][/_per-page/:per_page][/_order-by/:order_by][/_order-type/:order_type]]][:trailing_slash]',
                    'constraints' => [
                        'language' => '[a-z]{2}',
                        'page_name' => '[0-9a-z-/]*[0-9a-z-]{1}',
                        'category' => '[0-9a-zA-Z-]+',
                        'date'     => '[0-9]{4}/[0-9]{2}/[0-9]{2}',
                        'slug'     => '[0-9a-zA-Z-]+',
                        'page'     => '[0-9]+',
                        'per_page' => '[0-9]+',
                        'order_by' => '[a-z][a-z0-9-]*',
                        'order_type' => 'asc|desc',
                        'trailing_slash' => '/'
                        
                    ],
                    'defaults' => [
                        'controller' => 'Page',
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => false
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