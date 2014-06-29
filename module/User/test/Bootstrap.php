<?php
namespace User\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class UserBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

UserBootstrap::init();