<?php

namespace Payment\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class PaymentBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

PaymentBootstrap::init();