# CONFIG STRUCTURE

## Upload custom module structure.

    1. Each custom module must be archived by ZIP
    2. Each custom module must contains config.php into a root of archived module

        Example of upload config.php :

            return [
                'module_path' => 'module/Example', // it defines where the module directory located
                'layout_path' => 'layout/example'  // it defines where the module layout directory located
            ];

## Upload module updates structure.

    1. Each module updates must be archived by ZIP
    2. Each module updates must contains update_config.php into a root of archived module

        Example of upload update_config.php :

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