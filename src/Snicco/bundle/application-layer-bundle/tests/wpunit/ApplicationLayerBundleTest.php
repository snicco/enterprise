<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Tests\wpunit;

use Codeception\TestCase\WPTestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBusOption;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandLogger;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\RentMovieCommand;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\ReturnMovieCommand;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\TestApplicationService;
use stdClass;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

/**
 * @internal
 */
final class ApplicationLayerBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function that_the_alias_is_correct(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles.all', [ApplicationLayerBundle::class]);
        });

        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('snicco/application-layer-bundle'));
    }

    /**
     * @test
     */
    public function that_the_command_bus_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->boot();

        $this->assertCanBeResolved(CommandBus::class, $kernel);
    }

    /**
     * @test
     */
    public function that_a_command_can_be_run(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('command_bus', [
                CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
            ]);
        });
        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->instance(TestApplicationService::class, new TestApplicationService(new stdClass()));
        });

        $kernel->boot();

        /** @var CommandBus $bus */
        $bus = $kernel->container()
            ->get(CommandBus::class);

        $bus->handle($command = new RentMovieCommand());
        $this->assertTrue($command->handled);

        $bus->handle($command = new ReturnMovieCommand('foo_movie'));
        $this->assertTrue($command->returned);
    }

    /**
     * @test
     */
    public function that_the_command_map_is_generated_in_the_configuration(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('command_bus', [
                CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
            ]);
        });
        $kernel->boot();

        $config = $kernel->config();

        $this->assertSame([
            RentMovieCommand::class => [TestApplicationService::class, '__invoke'],
            ReturnMovieCommand::class => [TestApplicationService::class, 'returnMovie'],
        ], $config->getArray('command_bus.command-map'));
    }

    /**
     * @test
     */
    public function that_the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/command_bus.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/command_bus.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/command_bus.php';

        $this->assertSame(require dirname(__DIR__, 2) . '/config/command_bus.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/command_bus.php',
            '<?php return ' . var_export([
                CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/command_bus.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame([
            CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
        ], require $this->directories->configDir() . '/command_bus.php');
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/command_bus.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/command_bus.php'));
    }

    /**
     * @test
     */
    public function that_commands_are_logged(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('command_bus', [
                CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
            ]);
        });
        $logger = new TestLogger();
        $kernel->afterRegister(function (Kernel $kernel) use ($logger): void {
            $kernel->container()
                ->instance(TestApplicationService::class, new TestApplicationService(new stdClass()));
            $kernel->container()
                ->instance(LoggerInterface::class, $logger);
        });

        $kernel->boot();

        /** @var CommandBus $bus */
        $bus = $kernel->container()
            ->get(CommandBus::class);

        $this->assertFalse($logger->hasDebugRecords());

        $bus->handle($command = new ReturnMovieCommand('foo'));
        $this->assertTrue($command->returned);

        $this->assertTrue($logger->hasDebugRecords());
    }

    /**
     * @test
     */
    public function that_the_configured_psr3_logger_can_be_overwritten_by_using_the_command_logger_interface(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories,);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('command_bus', [
                CommandBusOption::APPLICATION_SERVICES => [TestApplicationService::class],
            ]);
        });
        $logger_psr = new TestLogger();
        $command_logger = new class() extends AbstractLogger implements CommandLogger {
            public array $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [$level, $message, $context];
            }
        };

        $kernel->afterRegister(function (Kernel $kernel) use ($logger_psr, $command_logger): void {
            $kernel->container()
                ->instance(TestApplicationService::class, new TestApplicationService(new stdClass()));
            $kernel->container()
                ->instance(LoggerInterface::class, $logger_psr);
            $kernel->container()
                ->instance(CommandLogger::class, $command_logger);
        });

        $kernel->boot();

        /** @var CommandBus $bus */
        $bus = $kernel->container()
            ->get(CommandBus::class);

        $this->assertCount(0, $command_logger->records);
        $this->assertFalse($logger_psr->hasDebugRecords());
        $bus->handle(new ReturnMovieCommand('foo'));
        $this->assertFalse($logger_psr->hasDebugRecords());
        $this->assertCount(2, $command_logger->records);
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
