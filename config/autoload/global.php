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

return array(
    'php_settings' => array(
        'mbstring.internal_encoding' => 'UTF-8'
    ),
    'session' => array(
        'config' => array(
            'class' => 'Zend\Session\Config\SessionConfig',
            'options' => array(
                'savePath' => APPLICATION_ROOT . '/data/sessions',
                'cookieSecure' => false,
                'cookieHttpOnly' => true
            )
        ),
        'storage' => 'Zend\Session\Storage\SessionArrayStorage',
        'validators' => array(
            'Zend\Session\Validator\RemoteAddr',
            'Zend\Session\Validator\HttpUserAgent',
        ),
        'save_handler' => null
    ),
    'static_cache' => array(
        'writable' => true,
        'readable' => true,
        'cache_dir' => APPLICATION_ROOT . '/data/cache/application',
        'dir_level' => 1,
        'ttl' => 0 // cache never will be expired
    ),
    'view_manager' => array(
        'layout' => 'layout/frontend'
    ),
    'dynamic_cache' => array(
        'writable' => true,
        'readable' => true
    ),
    'db' => array(
        'driver' => 'Pdo',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory'
        )
    ),
    'paths' => array(
        'layouts_cache_css' => 'layouts_cache/css',
        'layouts_cache_js' => 'layouts_cache/js',
        'config_cache' => 'data/cache/config',
        'captcha' => 'captcha',
        'captcha_font' => 'font/captcha.ttf',
        'resources' => 'resources',
        'layouts' => 'layouts',
    )
);
