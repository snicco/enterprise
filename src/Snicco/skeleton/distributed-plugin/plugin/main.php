<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\HttpKernelRunner;
use Snicco\Bundle\HttpRouting\WPAdminMenu;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\Kernel\ValueObject\Environment;
use VENDOR_NAMESPACE\Infrastructure\WordPress\VENDOR_NAMESPACE;

/*
|--------------------------------------------------------------------------
| Register the composer auto loader
|--------------------------------------------------------------------------
|
| Using the composer autoloader will be a lot faster than any custom
| autoloader if we are using class-maps in production.
|
*/
require __DIR__ . '/boot/autoloader.php';

/*
|--------------------------------------------------------------------------
| Parsing the environment
|--------------------------------------------------------------------------
|
| For a distributed WordPress plugin the environment
| should default to production.
| Overwriting the environment locally (during development) can be
| done in many ways, but a constant (defined in wp-config.php) is the most convenient.
|
*/
if (\defined('VENDOR_CAPS_PLUGIN_ENV')) {
    $debug = \defined('WP_DEBUG') && ((bool) WP_DEBUG);

    if (\defined('VENDOR_CAPS_PLUGIN_DEBUG')) {
        $debug = (bool) VENDOR_CAPS_PLUGIN_DEBUG;
    }

    $env = Environment::fromString((string) VENDOR_CAPS_PLUGIN_ENV, $debug);
} else {
    $env = Environment::prod();
}

/*
|--------------------------------------------------------------------------
| Creating the kernel
|--------------------------------------------------------------------------
|
| The kernel is created in a separate file, so that we can separate
| instantiation from booting. This will be needed in tests.
|
*/
/** @var Snicco\Component\Kernel\Kernel $kernel */
$kernel = (require_once __DIR__ . '/boot/create-kernel.php')($env);

/*
|--------------------------------------------------------------------------
| Turning on the lights
|--------------------------------------------------------------------------
|
| Booting the kernel will register all service definitions
| in the DI container. This process is very fast due to lazy loading.
| Services are only instantiated when you really need them.
|
*/
$kernel->boot();

/*
|--------------------------------------------------------------------------
| Exposing the public API of our plugin
|--------------------------------------------------------------------------
|
| If desired, we can expose a subset of the public API (use cases)
| of our plugin to third party developers/plugins.
|
| Usage: VENDOR_TITLE::plugin()->doSomething();
|
*/
VENDOR_NAMESPACE::expose($kernel->container());

/*
|--------------------------------------------------------------------------
| Serving the PHP process.
|--------------------------------------------------------------------------
|
| There are three main ways that our plugin can be invoked.
| 1) A web request.
| 2) As a command line script (eg. WP-CLI )
| 3) Web based wp-cron
|
| We differentiate between the two for maximum performance.
|
*/
$is_cli = $kernel->env()
    ->isCli();

$is_wp_cli = $is_cli && \defined('WP_CLI');

$is_cron = \wp_doing_cron();

/*
|--------------------------------------------------------------------------
| Serving a WP_CLI process
|--------------------------------------------------------------------------
|
| We lazy load all our registered commands into the global WP-CLI instance.
|
*/
if ($is_cli) {
    if (! $is_wp_cli) {
        return;
    }

    $wp_cli_application = $kernel->container()
        ->make(WPCLIApplication::class);

    $wp_cli_application->registerCommands();

    return;
}

/*
|--------------------------------------------------------------------------
| Serving WP_CRON
|--------------------------------------------------------------------------
|
| For now we have no special handling for WP_CRON.
| However we can't run our HTTP application for
| web based cron requests as fastcgi_finish_request is called inside
| wp-cron.php
*/
if ($is_cron) {
    return;
}

/*
|--------------------------------------------------------------------------
| Serving a HTTP request.
|--------------------------------------------------------------------------
|
| We resolve the HttpKernelRunner from the now booted container.
| We then listen for the correct time to run our application. (If the request matches one of our routes).
|
*/
$http_runner = $kernel->container()
    ->make(HttpKernelRunner::class);

$is_admin = \is_admin();

$http_runner->listen($is_admin);

/*
|--------------------------------------------------------------------------
| Admin Menu
|--------------------------------------------------------------------------
|
| If we are in the admin area we will also go ahead and attach our admin menu items
| from our route files to the WordPress admin menu.
|
*/
if ($is_admin) {
    $admin_menu = $kernel->container()
        ->make(WPAdminMenu::class);

    $admin_menu->setUp('VENDOR_TEXTDOMAIN');
}
