<?php
namespace Page\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class PageBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

PageBootstrap::init();