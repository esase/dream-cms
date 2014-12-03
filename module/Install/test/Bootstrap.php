<?php
namespace Install\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class InstallBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

InstallBootstrap::init();