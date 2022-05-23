<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication;

use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPAuthenticate;
use Snicco\Enterprise\Bundle\Auth\AuthModule;

final class AuthenticationModule extends AuthModule
{
    public function name(): string
    {
        return 'authentication';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->appendToList(
            'routing.' . RoutingOption::ROUTE_DIRECTORIES,
            __DIR__ . '/Http/routes'
        );
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container();
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();

        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_mapper->map('authenticate', WPAuthenticate::class, 9999);

        $event_dispatcher->subscribe(AuthenticationEventHandler::class);
    }
}
