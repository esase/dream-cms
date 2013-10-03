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
        'factories' => array(
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
            'Cache\Static' => function ($serviceManager)
            {
                $config = $serviceManager->get('Config');
                $cache =\Zend\Cache\StorageFactory::factory(array(
                    'adapter' => array(
                        'name' => $config['static_cache']['type']
                    ),
                    'plugins' => array(
                        // Don't throw exceptions on cache errors
                        'exception_handler' => array(
                            'throw_exceptions' => false
                        ),
                        'Serializer'
                    )
                ));

                $cache->setOptions($config['static_cache']['options']);
                return $cache;
            },
            'Cache\Dynamic' => function ($serviceManager)
            {
                $config = $serviceManager->get('Config');
                $cache = \Zend\Cache\StorageFactory::factory(array(
                    'adapter' => array(
                        'name' => $config['dynamic_cache']['type']
                    ),
                    'plugins' => array(
                        // Don't throw exceptions on cache errors
                        'exception_handler' => array(
                            'throw_exceptions' => false
                        ),
                        'Serializer'
                    )
                ));

                $cache->setOptions($config['dynamic_cache']['options']);
                return $cache;
            },
            'Zend\Session\SessionManager' => function ($serviceManager)
            {
                $config = $serviceManager->get('config');

                // get session config
                $sessionConfig = new
                $config['session']['config']['class']();
                $sessionConfig->setOptions($config['session']['config']['options']);

                // get session storage
                $sessionStorage = new $config['session']['storage']();

                $sessionSaveHandler = null;
                if (!empty($config['session']['save_handler'])) {
                    // class should be fetched from service manager since it
                    // will require constructor arguments
                    $sessionSaveHandler = $serviceManager->get($config['session']['save_handler']);
                }

                // get session manager
                $sessionManager = new \Zend\Session\SessionManager($sessionConfig,
                $sessionStorage, $sessionSaveHandler);
                
                if (!empty($config['session']['validators'])) {
                    $chain = $sessionManager->getValidatorChain();

                    foreach ($config['session']['validators'] as $validator) {
                        $chain->attach('session.validate', array(new $validator(), 'isValid'));
                    }
                }

                \Zend\Session\Container::setDefaultManager($sessionManager);
                return $sessionManager;
            }
        )
    )
);
