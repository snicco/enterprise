<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Shared\Infrastructure;

use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBusOption;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIOption;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Fortress
 */
abstract class FortressModule
{
    abstract public function name(): string;

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        // Do nothing
    }

    public function register(Kernel $kernel): void
    {
        // Do nothing
    }

    public function boot(Kernel $kernel): void
    {
        // Do nothing
    }

    /**
     * @param class-string[] $class_names
     */
    public function addCommandHandler(WritableConfig $config, array $class_names): void
    {
        $config->setIfMissing('command_bus.' . CommandBusOption::APPLICATION_SERVICES, []);
        $config->appendToList('command_bus.' . CommandBusOption::APPLICATION_SERVICES, $class_names);
    }

    /**
     * @param string[] $route_dirs
     */
    public function addRouteDirectories(WritableConfig $config, array $route_dirs): void
    {
        $config->setIfMissing('routing.' . RoutingOption::ROUTE_DIRECTORIES, []);
        $config->appendToList('routing.' . RoutingOption::ROUTE_DIRECTORIES, $route_dirs);
    }

    /**
     * @param string[] $route_dirs
     */
    public function addApiRouteDirectories(WritableConfig $config, array $route_dirs): void
    {
        $config->setIfMissing('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, []);
        $config->appendToList('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, $route_dirs);
    }

    /**
     * @param array<class-string<Command>> $command_classes
     */
    public function addCommands(WritableConfig $config, array $command_classes): void
    {
        $config->setIfMissing('better-wp-cli.' . BetterWPCLIOption::COMMANDS, []);
        $config->appendToList('better-wp-cli.' . BetterWPCLIOption::COMMANDS, $command_classes);
    }
}
