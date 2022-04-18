<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\GenerateCommandMap;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\MapCommandToApplicationService;
use Webmozart\Assert\Assert;

use function dirname;

final class ApplicationLayerBundle implements Bundle
{
    /**
     * @var string
     */
    private const ALIAS = 'snicco/application-layer-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/command_bus.php');

        $application_services = $config->getListOfStrings('command_bus.' . CommandBusOption::APPLICATION_SERVICES);
        Assert::allClassExists($application_services);
        $generate_map = new GenerateCommandMap($application_services);
        $config->set('command_bus.command-map', $generate_map());
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(CommandBus::class, function () use ($container, $config): CommandBus {
            /** @var array<class-string,class-string> $map */
            $map = $config->getArray('command_bus.command-map');

            $command_name_inflector = new ClassNameExtractor();
            $locator = new MapCommandToApplicationService($container, $map);

            $handler_middleware = new CommandHandlerMiddleware($command_name_inflector, $locator, $locator);

            return new CommandBus(new \League\Tactician\CommandBus([$handler_middleware]));
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }
}
