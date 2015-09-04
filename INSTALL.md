# INSTALLATION

## SYSTEM REQUIREMENTS

    1. Apache WEB server 2 or later.
    2. PHP 5.5 or later.
    3. MySQL 5 or later with InnoDb support.
    4. Memcached latest version or some of different cache engines such as: APC, WinCache, XCache.

## PERMISSIONS

    1. Writable
        1.  data/cache/application
        2.  data/cache/config
        3.  data/session
        4.  data/log
        5.  data/tmp
        6.  public_html/resource
        7.  public_html/layout_cache/css
        8.  public_html/layout_cache/js
        9.  public_html/captcha
        10. public_html/resource/user
        11. public_html/resource/user/thumbnail
        12. public_html/resource/filemanager
        13. config/autoload
        14. config/module/system.php
        15. config/module/custom.php

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