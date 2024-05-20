<?php

declare(strict_types=1);

// phpcs:disable PSR1

$testsDir = str_replace('\\', '/', __DIR__);
$libDir = dirname($testsDir);
$vendorDir = "{$libDir}/vendor";
$autoload = "{$vendorDir}/autoload.php";

if (!is_file($autoload)) {
    die('Please install via Composer before running tests.');
}

error_reporting(E_ALL);

require_once "{$vendorDir}/roots/wordpress-no-content/wp-includes/plugin.php";

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $autoload);
    require_once $autoload;
}

unset($libDir, $testsDir, $vendorDir, $autoload);
