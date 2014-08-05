<?php
namespace Localization\Test;

define('APPLICATION_ROOT', '../../../');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class LocalizationBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

LocalizationBootstrap::init();