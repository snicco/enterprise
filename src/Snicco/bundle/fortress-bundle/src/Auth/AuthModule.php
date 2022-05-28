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
use Snicco\Component\TestableClock\Clock;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIOption;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Console\Complete2FaCommand;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Console\Delete2FaCommand;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Console\Initialize2FaCommand;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Google2FaProvider;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Http\Controller\TwoFactorChallengeController;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\MappedEvent\WPAuthenticate;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorEventHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserProvider;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Infrastructure\UserProviderWPDB;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\FortressModule;
use Webmozart\Assert\Assert;

use function bin2hex;
use function dirname;
use function random_bytes;

final class AuthModule extends FortressModule
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
            dirname(__DIR__) . '/Auth/TwoFactor/Infrastructure/Http/routes',
        ]);

        $this->addCommands($config, [
            Initialize2FaCommand::class,
            Delete2FaCommand::class,
            Complete2FaCommand::class
        ]);
        
        if ($kernel->env()->isTesting()) {
            $config->setIfMissing(
                'fortress.auth.' . AuthModuleOption::TWO_FACTOR_CHALLENGE_HMAC_KEY,
                bin2hex(random_bytes(32))
            );
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
                $container[UserProvider::class],
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
            $table_name = $GLOBALS['wpdb']->prefix . $config->getString(
                'fortress.auth.' . AuthModuleOption::TWO_FACTOR_SETTINGS_TABLE_BASENAME
            );

            Assert::stringNotEmpty($table_name);

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
            $table_name = $GLOBALS['wpdb']->prefix . $config->getString(
                'fortress.auth.' . AuthModuleOption::TWO_FACTOR_CHALLENGES_TABLE_BASENAME
            );

            Assert::stringNotEmpty($table_name);

            return new TwoFactorChallengeRepositoryBetterWPDB(
                $container[BetterWPDB::class],
                $table_name,
                $container[Clock::class]
            );
        });

        $container->shared(
            TwoFactorChallengeService::class,
            fn (): TwoFactorChallengeService => new TwoFactorChallengeService(
                $config->getString('fortress.auth.' . AuthModuleOption::TWO_FACTOR_CHALLENGE_HMAC_KEY),
                $container[TwoFactorChallengeRepository::class],
                $container[Clock::class]
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

        $container->shared(UserProvider::class, fn (): UserProviderWPDB => new UserProviderWPDB());

        // Controller
        $container->shared(
            TwoFactorChallengeController::class,
            fn (): TwoFactorChallengeController => new TwoFactorChallengeController(
                $container[TwoFactorChallengeService::class],
                $container[OTPValidator::class],
            )
        );
        
        // Commands
        $container->shared(Initialize2FaCommand::class, function () use($container, $config){
            return new Initialize2FaCommand(
                $container[CommandBus::class],
                $container[UserProvider::class],
                $container[TwoFactorSecretGenerator::class],
                $config->getString('better-wp-cli.'.BetterWPCLIOption::NAME)
            );
        });
        
        $container->shared(Delete2FaCommand::class, function () use($container){
            return new Delete2FaCommand(
                $container[CommandBus::class],
                $container[UserProvider::class],
            );
        });
    
        $container->shared(Complete2FaCommand::class, function () use($container){
            return new Complete2FaCommand(
                $container[CommandBus::class],
                $container[UserProvider::class],
            );
        });
        
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
