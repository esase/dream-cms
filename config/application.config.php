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
use Zend\Stdlib\ArrayUtils;

$applicationConfigCache = APPLICATION_ROOT . '/data/cache/config';
$isConfigDirWritable = is_writable($applicationConfigCache);

define('SYSTEM_MODULES_CONFIG', __DIR__ . '/module/system.php');
define('CUSTOM_MODULES_CONFIG', __DIR__ . '/module/custom.php');

// define the application environment
if (!defined('APPLICATION_ENV')) {
    php_sapi_name() == 'cli'
        ? define('APPLICATION_ENV', 'console')
        : define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
}

// get list of modules
$systemModules = require_once SYSTEM_MODULES_CONFIG;
$customModules = require_once CUSTOM_MODULES_CONFIG;
$extraConfig   = require_once 'application.config.' . APPLICATION_ENV. '.php';

return ArrayUtils::merge([
    'modules' => array_merge($systemModules, $customModules),
    'module_listener_options' => [
        'config_glob_paths'    => [
            'config/autoload/{,*.}{global,local,' . APPLICATION_ENV . '}.php',
        ],
        'module_paths' => [
            './module',
            './vendor',
        ]
    ],
], $extraConfig);