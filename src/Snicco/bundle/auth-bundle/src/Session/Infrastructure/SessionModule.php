<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Infrastructure;

use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\AuthBundle\Module;
use Snicco\Enterprise\AuthBundle\Session\Application\SessionCommandHandler;
use Snicco\Enterprise\AuthBundle\Session\Domain\SessionManager;
use Snicco\Enterprise\AuthBundle\Session\Domain\SessionRepository;
use Snicco\Enterprise\AuthBundle\Session\Domain\TimeoutConfig;
use Snicco\Enterprise\AuthBundle\Session\Infrastructure\MappedEvent\SessionActivityRecorded;
use WP_User_Meta_Session_Tokens;

use function add_filter;
use function sprintf;

use const PHP_INT_MAX;

final class SessionModule extends Module
{
    public function name(): string
    {
        return 'session';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->setIfMissing('snicco_auth.timeouts', [
            'idle' => 60 * 15,
            'rotation' => 60 * 5,
        ]);
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
            fn () => new SessionEventHandler($c[SessionManager::class])
        );

        $c->shared(
            SessionCommandHandler::class,
            fn () => new SessionCommandHandler($c[SessionRepository::class])
        );

        $c->shared(SessionRepository::class, function () use ($c, $config) {
            $table_name = $GLOBALS['wpdb']->prefix . 'snicco_auth_sessions';

            return new SessionRepositoryBetterWPDB(
                $c[BetterWPDB::class],
                $table_name,
                SystemClock::fromUTC()
            );
        });

        $c->shared(SessionManager::class, function () use ($c, $config) {
            return new SessionManager(
                $c[EventDispatcher::class],
                new TimeoutConfig(
                    $config->getInteger('snicco_auth.timeouts.idle'),
                    $config->getInteger('snicco_auth.timeouts.rotation'),
                ),
                $c[SessionRepository::class],
                SystemClock::fromUTC()
            );
        });
    }

    public function boot(Kernel $kernel): void
    {
        $c = $kernel->container();

        $event_mapper = $c->make(EventMapper::class);
        $event_dispatcher = $c->make(EventDispatcher::class);

        $event_mapper->map('auth_cookie_valid', SessionActivityRecorded::class);

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
