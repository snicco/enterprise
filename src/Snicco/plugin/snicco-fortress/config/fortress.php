<?php

declare(strict_types=1);

use Snicco\Enterprise\Fortress\Auth\AuthModule;
use Snicco\Enterprise\Fortress\Auth\AuthModuleOption;
use Snicco\Enterprise\Fortress\Fail2Ban\Infrastructure\Fail2BanModule;
use Snicco\Enterprise\Fortress\Fail2Ban\Infrastructure\Fail2BanModuleOption;
use Snicco\Enterprise\Fortress\FortressOption;
use Snicco\Enterprise\Fortress\Password\Infrastructure\PasswordModule;
use Snicco\Enterprise\Fortress\Password\Infrastructure\PasswordModuleOption;
use Snicco\Enterprise\Fortress\Session\Infrastructure\SessionModule;
use Snicco\Enterprise\Fortress\Session\Infrastructure\SessionModuleOption;

return [
    FortressOption::MODULES => [
        PasswordModule::NAME,
        SessionModule::NAME,
        AuthModule::NAME,
        Fail2BanModule::NAME,
    ],

    FortressOption::ROUTE_PATH_PREFIX => '/auth',

    SessionModule::NAME => [
        SessionModuleOption::IDLE_TIMEOUT => 60 * 15,
        SessionModuleOption::ROTATION_INTERVAL => 60 * 10,
        SessionModuleOption::DB_TABLE_BASENAME => 'snicco_fortress_sessions',
    ],

    AuthModule::NAME => [
        AuthModuleOption::TWO_FACTOR_CHALLENGES_TABLE_BASENAME => 'snicco_fortress_2fa_challenges',
        AuthModuleOption::TWO_FACTOR_SETTINGS_TABLE_BASENAME => 'snicco_fortress_2fa_settings',
        AuthModuleOption::TWO_FACTOR_CHALLENGE_HMAC_KEY => SECURE_AUTH_SALT,
    ],

    PasswordModule::NAME => [
        PasswordModuleOption::PASSWORD_POLICY_EXCLUDED_ROLES => [],
    ],

    Fail2BanModule::NAME => [
        Fail2BanModuleOption::DAEMON => 'snicco_fortress',
        Fail2BanModuleOption::FLAGS => \LOG_PID,
        Fail2BanModuleOption::FACILITY => \LOG_AUTH,
    ],
];
