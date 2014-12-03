<?php
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
    'php_settings' => [
        'mbstring.internal_encoding' => 'UTF-8'
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
            'Zend\Session\Validator\RemoteAddr' => getEnv('REMOTE_ADDR'),
            'Zend\Session\Validator\HttpUserAgent' => getEnv('HTTP_USER_AGENT'),
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
            (defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : null) => 'SET NAMES \'UTF8\''
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory'
        ]
    ],
    'paths' => [
        'error_log' => APPLICATION_ROOT . '/data/log/log',
        'layout_cache_css' => 'layout_cache/css',
        'layout_cache_js' => 'layout_cache/js',
        'config_cache' => 'data/cache/config',
        'captcha' => 'captcha',
        'captcha_font' => 'font/captcha.ttf',
        'resource' => 'resource',
        'layout' => 'layout',
    ]
];
