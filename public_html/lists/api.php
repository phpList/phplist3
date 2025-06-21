<?php

use PhpList\Core\Core\Bootstrap;

if (version_compare(phpversion(), '8.1', '<')) {
  die('API is not supported on this PHP version.');
}
require_once __DIR__ . '/base/vendor/autoload.php';

Bootstrap::getInstance()
    ->configure()
    ->dispatch();
