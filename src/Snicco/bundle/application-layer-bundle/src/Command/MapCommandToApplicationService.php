<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use Psr\Container\ContainerInterface;
use Snicco\Component\StrArr\Str;

use Webmozart\Assert\Assert;

use function get_class;
use function lcfirst;
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
     * @var array<class-string, class-string>
     */
    private array $command_map;

    /**
     * @param array<class-string, class-string> $command_map
     */
    public function __construct(ContainerInterface $container, array $command_map)
    {
        $this->container = $container;
        $this->command_map = $command_map;
    }

    public function getHandlerForCommand($commandName): object
    {
        $application_service_class = $this->command_map[$commandName] ?? null;
        Assert::notNull(
            $application_service_class,
            sprintf('No application services is registered to handle the command [%s].', $commandName)
        );

        $application_service = $this->container->get($application_service_class);
        Assert::object($application_service);

        return $application_service;
    }

    public function inflect($command, $commandHandler): string
    {
        $class_name = get_class($command);

        $method_name = Str::afterLast($class_name, '\\');
        $method_name = Str::beforeLast($method_name, 'Command');

        return lcfirst($method_name);
    }
}
