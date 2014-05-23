<?php

namespace Membership\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class MembershipBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

MembershipBootstrap::init();