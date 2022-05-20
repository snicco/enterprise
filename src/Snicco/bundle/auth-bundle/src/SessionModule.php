<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use RuntimeException;
use WP_User_Meta_Session_Tokens;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Enterprise\Bundle\Auth\Session\WPAuthSessions;
use Snicco\Enterprise\Bundle\Auth\Session\TimeoutResolver;
use Snicco\Enterprise\Bundle\Auth\Session\SessionRepository;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Enterprise\Bundle\Auth\Session\SessionEventHandler;
use Snicco\Enterprise\Bundle\Auth\Session\Event\SessionActivityRecorded;

use function sprintf;

use const PHP_INT_MAX;

final class SessionModule extends AuthModule
{
    public function name() :string
    {
        return 'session';
    }
    
    public function configure(WritableConfig $config, Kernel $kernel) :void
    {
        $config->setIfMissing('snicco_auth.timeouts', [
            'idle' => 60 * 15,
            'rotation' => 60 * 5,
        ]);
    }
    
    public function register(Kernel $kernel) :void
    {
        $c = $kernel->container();
        $config = $kernel->config();
        
        $c->shared(
            SessionEventHandler::class,
            fn () => new SessionEventHandler($c[SessionRepository::class])
        );
        
        $c->shared(SessionRepository::class, function () use ($c, $config) {
            $table_name = $GLOBALS['wpdb']->prefix . 'snicco_auth_sessions';
        
            $timeout_resolver = new TimeoutResolver(
                $config->getInteger('snicco_auth.timeouts.idle'),
                $config->getInteger('snicco_auth.timeouts.rotation'),
            );
        
            return new SessionRepository(
                $c[EventDispatcher::class],
                $c[BetterWPDB::class],
                $timeout_resolver,
                $table_name,
            );
        });
    }
    
    public function boot(Kernel $kernel) :void
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
        
            $session_repo = $c->make(SessionRepository::class);
            WPAuthSessions::setSessionRepository($session_repo);
        
            return WPAuthSessions::class;
        }, PHP_INT_MAX - 1);
    
        $event_dispatcher->subscribe(SessionEventHandler::class);
    }
    
}