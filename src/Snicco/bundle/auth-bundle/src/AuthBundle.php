<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use PasswordHash;
use RuntimeException;
use Defuse\Crypto\Key;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Password\PasswordPluggable;
use Snicco\Enterprise\Bundle\Auth\Password\SecureWPPasswords;
use Snicco\Enterprise\Bundle\Auth\Session\Event\SessionActivityRecorded;
use Snicco\Enterprise\Bundle\Auth\Session\SessionEventHandler;
use Snicco\Enterprise\Bundle\Auth\Session\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\TimeoutResolver;
use Snicco\Enterprise\Bundle\Auth\Session\WPAuthSessions;
use WP_User_Meta_Session_Tokens;

use function defined;
use function add_filter;
use function sprintf;

use const WPINC;
use const ABSPATH;
use const PHP_INT_MAX;
use const SNICCO_AUTH_ENCRYPTION_SECRET;

final class AuthBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/auth-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->setIfMissing('snicco.auth', [
            'idle_timeout' => 60 * 15,
            'rotation_interval' => 60 * 5,
        ]);
        
        $config->setIfMissing('snicco.auth.features', [
            'passwords' => true,
            'session' => true,
        ]);
        
    }

    public function register(Kernel $kernel): void
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
                $config->getInteger('snicco.auth.idle_timeout'),
                $config->getInteger('snicco.auth.rotation_interval'),
            );

            return new SessionRepository(
                $c[EventDispatcher::class],
                $c[BetterWPDB::class],
                $timeout_resolver,
                $table_name,
            );
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        $config = $kernel->config();
        $container = $kernel->container();
        
        if($config->getBoolean('snicco.auth.features.passwords') && defined('SNICCO_AUTH_ENCRYPTION_SECRET')) {
            $this->bootPasswordModule($container);
        }
    
        if($config->getBoolean('snicco.auth.features.session')) {
           $this->bootSessionModule($kernel);
        }
        
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function bootSessionModule(Kernel $kernel): void
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
    
    private function bootPasswordModule(DIContainer $container) :void
    {
        $wp_hasher = $GLOBALS['wp_hasher'] ?? null;
        
        if ( ! $wp_hasher instanceof PasswordHash) {
            /** @psalm-suppress MissingFile */
            require_once ABSPATH.WPINC.'/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }
        
        PasswordPluggable::set(
            new SecureWPPasswords(
                $container[BetterWPDB::class],
                Key::loadFromAsciiSafeString(SNICCO_AUTH_ENCRYPTION_SECRET),
                $wp_hasher
            )
        );
        
        require_once __DIR__.'/Password/password-pluggable.php';
        
    }
    
}
