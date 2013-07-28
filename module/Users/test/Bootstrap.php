<?php

namespace Users\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class UsersBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

UsersBootstrap::init();