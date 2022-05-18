<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use RuntimeException;
use WP_User_Meta_Session_Tokens;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Enterprise\Bundle\Auth\SessionEventHandler;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Event\SessionActivityRecorded;

use function sprintf;
use function add_filter;

use const PHP_INT_MAX;

final class AuthBundle implements Bundle
{
    
    /**
     * @var string
     */
    public const ALIAS = 'snicco/auth-bundle';
    
    public function shouldRun(Environment $env) :bool
    {
        return true;
    }
    
    public function configure(WritableConfig $config, Kernel $kernel) :void
    {
        $config->setIfMissing('snicco.auth', [
            'idle_timeout' => 10,
            'rotation_interval' => 60 * 5,
        ]);
    }
    
    public function register(Kernel $kernel) :void
    {
        $container = $kernel->container();
        $config = $kernel->config();
        
        $container->shared(
            SessionEventHandler::class,
            fn() => new SessionEventHandler($container[SessionRepository::class])
        );
        
        $container->shared(SessionRepository::class, function () use ($container, $config) {
            $table_name = $GLOBALS['wpdb']->prefix.'snicco_auth_sessions';
    
            return new SessionRepository(
                $container[EventDispatcher::class],
                $container[BetterWPDB::class],
                $table_name,
                $config->getInteger('snicco.auth.idle_timeout'),
                $config->getInteger('snicco.auth.rotation_interval'),
            );
            
        });
    }
    
    public function bootstrap(Kernel $kernel) :void
    {
        $this->configureEvents($kernel);
    }
    
    public function alias() :string
    {
        return self::ALIAS;
    }
    
    private function configureEvents(Kernel $kernel) :void
    {
        $container = $kernel->container();
        
        $event_mapper = $container->make(EventMapper::class);
        $event_dispatcher = $container->make(EventDispatcher::class);
        
        $event_mapper->map('auth_cookie_valid', SessionActivityRecorded::class);
        
        add_filter('session_token_manager', function (string $class) use ($container) :string {
            if ($class !== WP_User_Meta_Session_Tokens::class) {
                throw new RuntimeException(
                    sprintf(
                        'snicco/auth-bundle uses a custom session token implementation but there is already another one [%s] hooked to the "session_token_manager" filer.',
                        $class
                    ),
                );
            }
            
            $session_repo = $container->make(SessionRepository::class);
            WPAuthSessions::setSessionRepository($session_repo);
            return WPAuthSessions::class;
        }, PHP_INT_MAX - 1);
        
        $event_dispatcher->subscribe(SessionEventHandler::class);
        
    }
    
}
