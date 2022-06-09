<?php

declare(strict_types=1);

use Snicco\Component\Kernel\Kernel;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

$wp_browser = getenv('WPBROWSER_HOST_REQUEST');
$cli = defined(WP_CLI::class);
if (! $cli) {
    return;
}

if (! $wp_browser) {
    return;
}

$kernel = new Kernel(
    new PimpleContainerAdapter(),
    Environment::testing(),
    Directories::fromDefaults(__DIR__)
);

$kernel->boot();

$cli = $kernel->container()->make(WPCLIApplication::class);

$cli->registerCommands();
