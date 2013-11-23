# INSTALLATION

## SYSTEM REQUIREMENTS

1. Apache WEB server 2 or later
2. PHP 5.4.0 or later.
3. MySQL 5 or later with InnoDb support
4. Memcached latest version

## PERMISSIONS

    1. Writable
        1. data/cache
        2. data/sessions
        3. config/modules/custom.php
        4. public/resources
        5. public/layouts_cache/css
        6. public/layouts_cache/js
        7. public/layouts/base
        8. public/layouts
        9. public/captcha

## APACHE SETTINGS

    1. Apache must be compiled with these extensions:
        1. mod_rewrite
        2. mod_env
        3. mod_expires

    2. Apache must allow to make changes with .htaccess file

## PHP SETTINGS

    1. PHP must be compiled with these extensions:
        1. memcached
        2. intl
        3. zlib

    2. PHP must be enabled with these options:
        1. fopen wrappers On