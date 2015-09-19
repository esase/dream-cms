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

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
return [
    'support_url' => 'http://dream-cms.kg',
    'installation_report_script' => 'install_report.php',
    'php_settings' => [
        'mbstring.internal_encoding' => 'UTF-8',
        'default_charset' => 'UTF-8'
    ],
    'session' => [
        'config' => [
            'class' => 'Zend\Session\Config\SessionConfig',
            'options' => [
                'savePath' => APPLICATION_ROOT . '/data/session',
                'cookieSecure' => false,
                'cookieHttpOnly' => true
            ]
        ],
        'storage' => 'Zend\Session\Storage\SessionArrayStorage',
        'validators' => [
            'Zend\Session\Validator\RemoteAddr',
            'Zend\Session\Validator\HttpUserAgent'
        ],
        'save_handler' => null
    ],
    'static_cache' => [
        'writable' => true,
        'readable' => true,
        'cache_dir' => APPLICATION_ROOT . '/data/cache/application',
        'dir_level' => 1,
        'ttl' => 0 // cache never will be expired
    ],
    'view_manager' => [
        'layout' => 'layout/frontend'
    ],
    'dynamic_cache' => [
        'writable' => true,
        'readable' => true
    ],
    'db' => [
        'driver' => 'Pdo',
        'driver_options' => [
            '1002' => 'SET NAMES \'UTF8\''
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory'
        ]
    ],
    'paths' => [
        'error_log' => APPLICATION_ROOT . '/data/log/log',
        'module' => 'module',
        'module_view' => 'view',
        'custom_module_config' => 'config/module/custom.php',
        'layout' => 'layout',
        'layout_base' => 'layout/base',
        'layout_cache_css' => 'layout_cache/css',
        'layout_cache_js' => 'layout_cache/js',
        'config_cache' => 'data/cache/config',
        'tmp' => 'data/tmp',
        'captcha' => 'captcha',
        'captcha_font' => 'font/captcha.ttf',
        'resource' => 'resource',
        'layout' => 'layout',
    ]
];
