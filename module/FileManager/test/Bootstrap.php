<?php

namespace FileManager\Test;

define('APPLICATION_ROOT', '../../../');
define('APPLICATION_PUBLIC', APPLICATION_ROOT . 'public');
require_once APPLICATION_ROOT . 'init_tests_autoloader.php';

use UnitTestBootstrap;

class FileManagerBootstrap extends UnitTestBootstrap\UnitTestBootstrap
{}

FileManagerBootstrap::init();