<?php
namespace Layout\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class LayoutBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

LayoutBootstrap::init();