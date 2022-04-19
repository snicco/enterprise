<?php

declare(strict_types=1);

if (\file_exists($scoper_autoloader = \dirname(__DIR__) . '/vendor/scoper-autoload.php')) {
    require_once $scoper_autoloader;
} elseif (\file_exists($composer_autoloader = \dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once $composer_autoloader;
} else {
    throw new RuntimeException('VENDOR_TITLE plugin was not installed correctly. Autoloader is missing.');
}
