<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\Fortress\FortressBundle;

use function dirname;

/**
 * @internal
 */
final class AuthBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_the_http_routing_bundle_is_not_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles', [
                Environment::ALL => [
                    FortressBundle::class,
                    BetterWPHooksBundle::class,
                    BetterWPDBBundle::class,
                    ApplicationLayerBundle::class,
                ],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('snicco/http-routing-bundle');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_the_application_layer_bundle_is_not_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles', [
                Environment::ALL => [
                    FortressBundle::class,
                    BetterWPHooksBundle::class,
                    BetterWPDBBundle::class,
                    HttpRoutingBundle::class,
                ],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('snicco/application-layer-bundle');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function that_the_bundle_is_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('snicco/auth-bundle'));
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures/test-app';
    }
}
