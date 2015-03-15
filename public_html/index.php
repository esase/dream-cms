<?php

date_default_timezone_set('UTC');

define('APPLICATION_START', microtime(true));
define('APPLICATION_PUBLIC', __DIR__);
define('APPLICATION_ROOT', dirname(APPLICATION_PUBLIC));

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(APPLICATION_ROOT);

// Setup autoloading
require_once 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
