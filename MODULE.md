# CONFIG STRUCTURE

## Custom module install config structure.

    1. Each custom module must contains config/module.config.install.php into a root of a module

        Example of module.config.install.php :

            return [
                'version' => '1.0.0',
                'vendor' => 'eSASe',
                'vendor_email' => 'alexermashev@gmail.com',
                'description' => 'Just an example',
                'system_requirements' => [ // list of system requirements
                    'php_extensions' => [
                        'intl',
                        'curl'
                    ],
                    'php_settings' => [
                        'allow_url_fopen' => 1,
                        'file_uploads' => 1
                    ],
                    'php_enabled_functions' => [
                        'exec'
                    ],
                    'php_version' => '5.4.0' // specify here needed PHP version or write null
                ],
                'module_depends' => [ // list dependent modules
                    [
                        'module' => 'Example2',
                        'vendor' => 'eSASe',
                        'vendor_email' => 'alexermashev@gmail.com'
                    ]
                ],
                'clear_caches' => [
                    'setting'       => false,
                    'time_zone'     => false,
                    'admin_menu'    => true,
                    'js_cache'      => true,
                    'css_cache'     => true,
                    'layout'        => false,
                    'localization'  => false,
                    'page'          => true,
                    'user'          => false,
                    'xmlrpc'        => false
                ],
                'resources' => [ // create needed resources dirs
                    [
                        'dir_name' => 'example',
                        'is_public' => true
                    ],
                    [
                        'dir_name' => 'example/thumbnail',
                        'is_public' => false // it creates .htaccess with "Deny from all"
                    ],
                    [
                        'dir_name' => 'example/big',
                        'is_public' => false
                    ]
                ],
                'install_sql' => __DIR__ . '/../install/install.sql', // run install sql
                'install_intro' => 'Example install into', // Show this text after installation
                'uninstall_sql' => __DIR__ . '/../install/uninstall.sql', // run uninstall sql
                'uninstall_intro' => 'Example uninstall into', // Show this text after uninstallation
                'layout_path' => 'example' // path to module's layout dir with css, js and images
            ];

## Upload custom module config structure.

    1. Each uploadable custom module must be archived by ZIP
    2. Each uploadable custom module must contains config.php into a root of archived module

        Example of config.php :

            return [
                'module_path' => 'module/Example', // it defines where the module directory located
                'layout_path' => 'layout/example'  // it defines where the module layout directory located
            ];

## Upload module updates config structure.

    1. Each uploadable module updates must be archived by ZIP
    2. Each uploadable module updates must contains update_config.php into a root of archived module

        Example of update_config.php :

            return [
                'module' => 'Example',
                'version' => '1.1.0',
                'vendor' => 'eSASe',
                'vendor_email' => 'alexermashev@gmail.com',
                'module_path' => 'module', // it defines where the module directory located
                'layout_path' => 'layout', // it defines where the module layout directory located
                'update_sql' => 'update.sql', // update sql (run only for installed modules)
                'clear_caches' => [ // clear caches (run only for installed modules)
                    'setting'       => true,
                    'time_zone'     => true,
                    'admin_menu'    => true,
                    'js_cache'      => true,
                    'css_cache'     => true,
                    'layout'        => true,
                    'localization'  => true,
                    'page'          => true,
                    'user'          => true,
                    'xmlrpc'        => true
                ],
                'create_resources' => [ // create additional resources dirs (run only for installed modules)
                    [
                        'dir_name' => 'example/big',
                        'is_public' => false
                    ]
                ],
                'delete_resources' => [ // delete unnecessary resources dirs
                ],
            ];