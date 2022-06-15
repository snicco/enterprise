<?php

declare(strict_types=1);

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Fortress\Auth\AuthModuleOption;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionModuleOption;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionRepositoryBetterWPDB;

$kernel = new Kernel(
    new PimpleContainerAdapter(),
    Environment::testing(),
    Directories::fromDefaults(__DIR__)
);

$kernel->boot();
$config = $kernel->config();

$cli = $kernel->container()
    ->make(WPCLIApplication::class);

$cli->registerCommands();

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
