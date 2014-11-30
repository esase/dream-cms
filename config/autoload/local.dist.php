<?php
/**
 * Local Configuration Override
 *
 * This configuration override file is for overriding environment-specific and
 * security-sensitive configuration information. Copy this file without the
 * .dist extension at the end and populate values as needed.
 *
 * @NOTE: This file is ignored from Git by default with the .gitignore included
 * in ZendSkeletonApplication. This is a good practice, as it prevents sensitive
 * credentials from accidentally being committed into version control.
 */

return [
    'db' => [
        'dsn' => 'mysql:dbname=__YOUR_DB_NAME__;host=__YOUR_DB_HOST__',
        'username' => '__YOUR_DB_USER_NAME__',
        'password' => '__YOUR_DB_PASSWORD__',
    ],
];
