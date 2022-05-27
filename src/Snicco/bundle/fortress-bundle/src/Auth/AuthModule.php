<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth;

use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Infrastructure\Http\Controller\TwoFactorChallengeController;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Google2FaProvider;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\MappedEvent\WPAuthenticate;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorEventHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Module;

use function bin2hex;
use function dirname;
use function random_bytes;

final class AuthModule extends Module
{
    /**
     * @var string
     */
    public const NAME = 'auth';

    public function name(): string
    {
        return self::NAME;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->addCommandHandler($config, [
            TwoFactorCommandHandler::class,
        ]);

        $this->addRouteDirectories($config, [
            dirname(__DIR__) . '/Auth/Authenticator/Infrastructure/Http/routes',
        ]);

        $config->setIfMissing('snicco_auth.authentication.table_names', [
            '2fa_settings' => 'snicco_auth_2fa_settings',
            '2fa_challenges' => 'snicco_auth_2fa_challenges',
        ]);

        if ($kernel->env()->isTesting()) {
            $config->setIfMissing('snicco_auth.authentication.2fa_challenge_hmac', bin2hex(random_bytes(32)));
        }
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(
            TwoFactorCommandHandler::class,
            fn (): TwoFactorCommandHandler => new TwoFactorCommandHandler(
                $container[TwoFactorSettings::class],
                $container[OTPValidator::class]
            )
        );

        $container->shared(
            TwoFactorSecretGenerator::class,
            fn (): Google2FaProvider => $container[Google2FaProvider::class]
        );

        $container->shared(TwoFactorSettings::class, function () use (
            $container,
            $config
        ): TwoFactorSettingsBetterWPDB {
            /** @var non-empty-string $table_name */
            $table_name = $GLOBALS['wpdb']->prefix . $config->getString(
                'snicco_auth.authentication.table_names.2fa_settings'
            );

            return new TwoFactorSettingsBetterWPDB(
                $container[BetterWPDB::class],
                $table_name
            );
        });

        $container->shared(Google2FaProvider::class, fn (): Google2FaProvider => new Google2FaProvider(
            new Google2FA(),
            $container[TwoFactorSettings::class]
        ));

        $container->shared(
            OTPValidator::class,
            fn (): Google2FaProvider => $container[Google2FaProvider::class]
        );

        $container->shared(TwoFactorChallengeRepository::class, function () use (
            $container,
            $config
        ): TwoFactorChallengeRepositoryBetterWPDB {
            /** @var non-empty-string $table_name */
            $table_name = $GLOBALS['wpdb']->prefix . $config->getString(
                'snicco_auth.authentication.table_names.2fa_challenges'
            );

            return new TwoFactorChallengeRepositoryBetterWPDB(
                $container[BetterWPDB::class],
                $table_name
            );
        });

        $container->shared(
            TwoFactorChallengeService::class,
            fn (): TwoFactorChallengeService => new TwoFactorChallengeService(
                $config->getString('snicco_auth.authentication.2fa_challenge_hmac'),
                $container[TwoFactorChallengeRepository::class],
            )
        );

        $container->shared(
            TwoFactorEventHandler::class,
            fn (): TwoFactorEventHandler => new TwoFactorEventHandler(
                $container[TwoFactorSettings::class],
                $container[TwoFactorChallengeService::class],
                $container[EventDispatcher::class],
                $container[UrlGenerator::class]
            )
        );

        // Controller
        $container->shared(
            TwoFactorChallengeController::class,
            fn (): TwoFactorChallengeController => new TwoFactorChallengeController(
                $container[TwoFactorChallengeService::class]
            )
        );
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();

        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_mapper->map('authenticate', WPAuthenticate::class, 9999);

        $event_dispatcher->subscribe(TwoFactorEventHandler::class);
    }
}
