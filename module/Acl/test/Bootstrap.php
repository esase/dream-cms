<?php
namespace Acl\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class AclBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

AclBootstrap::init();