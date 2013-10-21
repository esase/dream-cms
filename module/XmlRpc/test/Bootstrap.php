<?php

namespace XmlRpc\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class XmlRpcBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

XmlRpcBootstrap::init();