# INSTALLATION

## SYSTEM REQUIREMENTS

1. Apache WEB server 2 or later
2. PHP 5.3.3 or later.
3. MySQL 5 or later with InnoDb support

## PERMISSIONS

    1. Writable
        a. data/cache
        b. data/sessions
        c. config/modules/custom.php
        d. public/layouts/base/css
        i. public/layouts/base/js

## APACHE SETTINGS

    1. Apache must be compiled with these extensions:
        a. mod_rewrite
        b. mod_env

    2. Apache should allow to make changes with .htaccess file

## PHP SETTINGS

    1. PHP must be compiled with these extensions:
        a. memcached
        b. intl