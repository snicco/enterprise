<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Infrastructure;

use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\Fortress\Session\Application\SessionCommandHandler;
use Snicco\Enterprise\Fortress\Session\Domain\SessionManager;
use Snicco\Enterprise\Fortress\Session\Domain\SessionRepository;
use Snicco\Enterprise\Fortress\Session\Domain\TimeoutConfig;
use Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent\AuthCookieValid;
use Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent\SetLoginCookie;
use Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent\WPLogout;
use Snicco\Enterprise\Fortress\Shared\Infrastructure\FortressModule;
use Webmozart\Assert\Assert;
use WP_User_Meta_Session_Tokens;

use function add_filter;
use function sprintf;

use const PHP_INT_MAX;

final class SessionModule extends FortressModule
{
    /**
     * @var string
     */
    public const NAME = 'session';

    public function name(): string
    {
        return self::NAME;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->addCommandHandler($config, [
            SessionCommandHandler::class,
        ]);
    }

    public function register(Kernel $kernel): void
    {
        $c = $kernel->container();
        $config = $kernel->config();

        $c->shared(
            SessionEventHandler::class,
            fn (): SessionEventHandler => new SessionEventHandler(
                $c[SessionManager::class],
                'snicco_fortress_remember_me'
            )
        );

        $c->shared(
            SessionCommandHandler::class,
            fn (): SessionCommandHandler => new SessionCommandHandler($c[SessionManager::class])
        );

        $c->shared(SessionRepository::class, function () use ($c, $config): SessionRepositoryBetterWPDB {
            $table_name = $GLOBALS['wpdb']->prefix
                          . $config->getString('fortress.session.' . SessionModuleOption::DB_TABLE_BASENAME);

            Assert::stringNotEmpty($table_name);

            return new SessionRepositoryBetterWPDB(
                $c[BetterWPDB::class],
                $table_name,
                SystemClock::fromUTC()
            );
        });

        $c->shared(SessionManager::class, fn (): SessionManager => new SessionManager(
            $c[EventDispatcher::class],
            new TimeoutConfig(
                $config->getInteger('fortress.session.' . SessionModuleOption::IDLE_TIMEOUT),
                $config->getInteger('fortress.session.' . SessionModuleOption::ROTATION_INTERVAL),
            ),
            $c[SessionRepository::class],
            SystemClock::fromUTC()
        ));
    }

    public function boot(Kernel $kernel): void
    {
        $c = $kernel->container();

        $event_mapper = $c->make(EventMapper::class);
        $event_dispatcher = $c->make(EventDispatcher::class);

        $event_mapper->map('auth_cookie_valid', AuthCookieValid::class);
        $event_mapper->map('set_logged_in_cookie', SetLoginCookie::class);
        $event_mapper->map('wp_logout', WPLogout::class);

        add_filter('session_token_manager', function (string $class) use ($c): string {
            if (WP_User_Meta_Session_Tokens::class !== $class) {
                throw new RuntimeException(
                    sprintf(
                        'snicco/auth-bundle uses a custom session token implementation but there is already another one [%s] hooked to the "session_token_manager" filer.',
                        $class
                    ),
                );
            }

            WPAuthSessionTokens::setSessionManager($c[SessionManager::class]);

            return WPAuthSessionTokens::class;
        }, PHP_INT_MAX - 1);

        $event_dispatcher->subscribe(SessionEventHandler::class);
    }
}
