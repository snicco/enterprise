<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\HttpKernelRunner;
use Snicco\Bundle\HttpRouting\WPAdminMenu;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Fortress\Auth\AuthModuleOption;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\Fortress\Password\Infrastructure\SecureWPPasswords;
use Snicco\Enterprise\Fortress\Session\Infrastructure\SessionModuleOption;
use Snicco\Enterprise\Fortress\Session\Infrastructure\SessionRepositoryBetterWPDB;

\defined('ABSPATH') || die('Forbidden');

/*
|--------------------------------------------------------------------------
| Register the composer auto loader
|--------------------------------------------------------------------------
|
*/
require_once __DIR__ . '/boot/autoloader.php';

/*
|--------------------------------------------------------------------------
| Parsing the environment
|--------------------------------------------------------------------------
|
*/
$debug = \filter_var(
    ($_SERVER['SNICCO_FORTRESS_DEBUG'] ?? (\defined('WP_DEBUG') && (bool) WP_DEBUG)),
    \FILTER_VALIDATE_BOOLEAN
);
$env = Environment::fromString('dev', $debug);

/*
|--------------------------------------------------------------------------
| Creating the kernel
|--------------------------------------------------------------------------
|
| The kernel is created in a separate file, so that we can separate
| instantiation from booting. This will be needed in tests.
|
*/
/** @var Kernel $kernel */
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
$config = $kernel->config();
$container = $kernel->container();

/*
|--------------------------------------------------------------------------
| Activation
|--------------------------------------------------------------------------
|
*/
$db = BetterWPDB::fromWpdb();
$db_prefix = $GLOBALS['wpdb']->prefix;
TwoFactorSettingsBetterWPDB::createTable(
    $db,
    $db_prefix . $config->getString('fortress.auth.' . AuthModuleOption::TWO_FACTOR_SETTINGS_TABLE_BASENAME)
);
TwoFactorChallengeRepositoryBetterWPDB::createTable(
    $db,
    $db_prefix . $config->getString('fortress.auth.' . AuthModuleOption::TWO_FACTOR_CHALLENGES_TABLE_BASENAME)
);
SessionRepositoryBetterWPDB::createTable(
    $db,
    $db_prefix . $config->getString('fortress.session.' . SessionModuleOption::DB_TABLE_BASENAME)
);
SecureWPPasswords::alterTable($GLOBALS['wpdb']);

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
| We differentiate between the three for maximum performance.
|
*/
$is_cli = $env->isCli();
$is_wp_cli = $is_cli && \defined(WP_CLI::class);
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

    $wp_cli_application = $container->make(WPCLIApplication::class);

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
$http_runner = $container->make(HttpKernelRunner::class);

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

    $admin_menu->setUp('snicco-fortress');
}
