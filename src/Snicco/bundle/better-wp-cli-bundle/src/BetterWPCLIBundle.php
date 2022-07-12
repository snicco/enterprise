<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\BetterWPCLI;

use Closure;
use RuntimeException;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\CommandLoader\ArrayCommandLoader;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function copy;
use function dirname;
use function is_file;
use function sprintf;

final class BetterWPCLIBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/better-wp-cli-bundle';

    public function shouldRun(Environment $env): bool
    {
        return $env->isCli();
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/better-wp-cli.php');
        $this->copyConfiguration($kernel);
    }

    public function register(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(WPCLIApplication::class, function () use ($container, $config): WPCLIApplication {
            $name = $config->getString('better-wp-cli.' . BetterWPCLIOption::NAME);

            /** @var class-string<Command>[] $commands */
            $commands = $config->getListOfStrings('better-wp-cli.' . BetterWPCLIOption::COMMANDS);

            return new WPCLIApplication($name, new ArrayCommandLoader(
                $commands,
                $this->commandFactory($container)
            ));
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    /**
     * @return Closure(class-string<Command>):Command
     */
    private function commandFactory(DIContainer $container): Closure
    {
        return fn (string $command_class): object => $container->make($command_class);
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/better-wp-cli.php';

        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/better-wp-cli.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Could not copy the default templating config to destination [%s]', $destination)
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
