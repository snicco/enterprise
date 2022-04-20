<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Logger\Formatter\ClassPropertiesFormatter;
use League\Tactician\Logger\LoggerMiddleware;
use League\Tactician\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBusOption;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandLogger;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\GenerateCommandMap;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\MapCommandToApplicationService;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\Middleware\BetterWPDBTransaction;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\ProductionCommandBus;
use Webmozart\Assert\Assert;

use function array_map;
use function copy;
use function dirname;
use function is_file;
use function sprintf;

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

    public function alias(): string
    {
        return self::ALIAS;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/command_bus.php');

        Assert::allClassExists($config->getListOfStrings('command_bus.' . CommandBusOption::MIDDLEWARE));

        $this->copyConfiguration($kernel);
        // This is intentionally after the configuration copy.
        // We don't want the command map in the configuration, only in the cache.
        $this->configureCommandMap($config);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(CommandBus::class, function () use ($container, $config): CommandBus {
            /** @var class-string<Middleware>[] $middleware_classes */
            $middleware_classes = $config->getListOfStrings('command_bus.' . CommandBusOption::MIDDLEWARE);

            $middleware = array_map(
                fn (string $class): Middleware => $container[$class] ?? new $class(),
                $middleware_classes
            );

            return new ProductionCommandBus(new \League\Tactician\CommandBus($middleware));
        });

        $this->bindCommandHandlerMiddleware($container, $config);
        $this->bindLoggerMiddleware($container);
        $this->bindWPDBTransactionMiddleware($container);
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    private function bindCommandHandlerMiddleware(DIContainer $container, ReadOnlyConfig $config): void
    {
        $container->shared(CommandHandlerMiddleware::class, function () use (
            $container,
            $config
        ): CommandHandlerMiddleware {
            /** @var array<class-string, array{0:class-string, 1:string}> $map */
            $map = $config->getArray('command_bus.command-map');

            $name_extractor = new ClassNameExtractor();

            $locator = new MapCommandToApplicationService($container, $map);

            return new CommandHandlerMiddleware($name_extractor, $locator, $locator);
        });
    }

    private function bindLoggerMiddleware(DIContainer $container): void
    {
        $container->shared(LoggerMiddleware::class, fn (): LoggerMiddleware => new LoggerMiddleware(
            new ClassPropertiesFormatter(),
            $container[CommandLogger::class] ?? $container[LoggerInterface::class] ?? new NullLogger()
        ));
    }

    private function configureCommandMap(WritableConfig $config): void
    {
        $application_services = $config->getListOfStrings('command_bus.' . CommandBusOption::APPLICATION_SERVICES);

        Assert::allClassExists($application_services);

        $generate_map = new GenerateCommandMap($application_services);
        $config->set('command_bus.command-map', $generate_map());
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/command_bus.php';

        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/command_bus.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Could not copy the default templating config to destination [%s]', $destination)
            );
            // @codeCoverageIgnoreEnd
        }
    }

    private function bindWPDBTransactionMiddleware(DIContainer $container): void
    {
        $container->shared(
            BetterWPDBTransaction::class,
            fn (): BetterWPDBTransaction => new BetterWPDBTransaction(
                $container[BetterWPDB::class] ?? BetterWPDB::fromWpdb()
            )
        );
    }
}
