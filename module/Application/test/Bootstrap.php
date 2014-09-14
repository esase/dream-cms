<?php
namespace Application\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';;

use UnitTestBootstrap;

class ApplicationBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

ApplicationBootstrap::init();