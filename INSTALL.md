# INSTALLATION

## SYSTEM REQUIREMENTS

    1. Apache WEB server 2 or later.
    2. PHP 5.4.0 or later.
    3. MySQL 5 or later with InnoDb support.
    4. Memcached latest version or some of different cache engines such as: APC, WinCache, XCache.

## PERMISSIONS

    1. Writable
        1.  data/cache/application
        2.  data/cache/config
        3.  data/session
        4.  config/module/custom.php
        5.  public/resource
        6.  public/layout_cache/css
        7.  public/layout_cache/js
        8.  public/captcha
        9.  public/resource/user
        10. public/resource/user/thumbnail
        11. data/log
        12. public/resource/filemanager
        13. config/autoload
        14. config/module/system.php

## APACHE SETTINGS

    1. Apache must be compiled with these extensions:
        1. mod_rewrite
        2. mod_env
        3. mod_expires

    2. Apache must allow to make changes with .htaccess file.

## PHP SETTINGS

    1. PHP must be compiled with these extensions:
        1. memcached or xcache or apc or wincache
        2. intl
        3. zlib
        4. gd
        5. fileinfo
        6. zip
        7. pdo_mysql
        8. curl
        9. mbstring

    2. PHP must be enabled with these options:
        1. allow_url_fopen 1
        2. file_uploads 1

## CRON JOBS

    1. */5 * * * * /replace/it/with/path/to/php/binary -q /replace/it/with/application/public/path/index.php application send messages &> /dev/null