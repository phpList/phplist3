<?php
declare(strict_types=1);

use PhpList\Core\Core\Bootstrap;

require_once __DIR__ . '/base/vendor/autoload.php';

Bootstrap::getInstance()
    ->configure()
    ->dispatch();
