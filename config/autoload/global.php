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
    'default_timezone' => 'Europe/Moscow',
    'php_settings' => array(
        'mbstring.internal_encoding' => 'UTF-8'
    ),
    'session' => array(
        'config' => array(
            'class' => 'Zend\Session\Config\SessionConfig',
            'options' => array(
                'savePath' => APPLICATION_ROOT . '/data/sessions',
                'cookieLifetime' => 0,
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
        'type' => 'filesystem',
        'options' => array(
            'writable' => true,
            'readable' => true,
            'cache_dir' => APPLICATION_ROOT . '/data/cache',
            'dir_level' => 1,
            'ttl' => 0 // cache never will be expired
        )
    ),
    'dynamic_cache' => array(
        'type' => 'memcached',
        'options' => array(
            'writable' => true,
            'readable' => true,
            'ttl' => 600, // 10 minutes,
            'servers' => array(
                'localhost',
                11211
            )
        )
    ),
    'db' => array(
        'driver' => 'Pdo',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
    ),
    'service_manager' => array(
        'factories' => array()
    )
);
