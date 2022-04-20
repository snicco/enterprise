<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function get_class;
use function sprintf;

/**
 * @interal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\ApplicationLayer
 */
final class MapCommandToApplicationService implements HandlerLocator, MethodNameInflector
{
    private ContainerInterface $container;

    /**
     * @var array<class-string, array{0:class-string, 1:string}>
     */
    private array $command_map;

    /**
     * @param array<class-string, array{0:class-string, 1:string}> $command_map
     */
    public function __construct(ContainerInterface $container, array $command_map)
    {
        $this->container = $container;
        $this->command_map = $command_map;
    }

    public function getHandlerForCommand($commandName): object
    {
        $application_service_mapping = $this->command_map[$commandName] ?? null;
        Assert::notNull(
            $application_service_mapping,
            sprintf('No application services is registered to handle the command [%s].', $commandName)
        );

        $application_service = $this->container->get($application_service_mapping[0]);
        Assert::object($application_service);

        return $application_service;
    }

    public function inflect($command, $commandHandler): string
    {
        $name = get_class($command);
        $application_service_mapping = $this->command_map[$name] ?? null;
        Assert::notNull(
            $application_service_mapping,
            sprintf('No application services is registered to handle the command [%s].', $name)
        );

        return $application_service_mapping[1];
    }
}
