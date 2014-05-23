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
        8.  public/layout/base
        9.  public/layout
        10. public/captcha
        11. public/resource/user
        12. public/resource/user/thumbnail
        13. data/log/log
        14. public/resource/file_manager
        15. public/resource/membership

## APACHE SETTINGS

    1. Apache must be compiled with these extensions:
        1. mod_rewrite
        2. mod_env
        3. mod_expires

    2. Apache must allow to make changes with .htaccess file.

## PHP SETTINGS

    1. PHP must be compiled with these extensions:
        1. memcached
        2. intl
        3. zlib
        4. gd
        5. SplFileInfo

    2. PHP must be enabled with these options:
        1. fopen wrappers On