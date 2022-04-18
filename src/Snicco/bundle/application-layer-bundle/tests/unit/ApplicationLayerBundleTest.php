<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\RentMovieCommand;

use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\ReturnMovieCommand;

use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\TestApplicationService;
use stdClass;

use function dirname;

/**
 * @internal
 */
final class ApplicationLayerBundleTest extends Unit
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

        $kernel->boot();

        $config = $kernel->config();

        $this->assertSame([
            RentMovieCommand::class => TestApplicationService::class,
            ReturnMovieCommand::class => TestApplicationService::class,
        ], $config->getArray('command_bus.command-map'));
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
