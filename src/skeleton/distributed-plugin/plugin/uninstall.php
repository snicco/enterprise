<?php

declare(strict_types=1);

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use VENDOR_NAMESPACE\Infrastructure\WordPress\UninstallPlugin;

if (! \defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

require __DIR__ . '/boot/autoloader.php';

/** @var Kernel $kernel */
$kernel = (require __DIR__ . '/boot/create-kernel.php')(Environment::prod());

$kernel->boot();

$uninstall = new UninstallPlugin(
    $kernel->container()[BetterWPDB::class],
    $kernel->config()
        ->getString('ebooks.table'),
    \defined('VENDOR_CAPS_DELETE_DATA_ON_DELETION') && (bool) VENDOR_CAPS_DELETE_DATA_ON_DELETION
);
$uninstall();
