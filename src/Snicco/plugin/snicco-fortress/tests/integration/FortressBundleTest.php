<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\integration;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Fortress\FortressBundle;
use Snicco\Enterprise\Fortress\FortressOption;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

/**
 * @internal
 */
final class FortressBundleTest extends WPTestCase
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
                    EncryptionBundle::class,
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
    public function that_an_exception_is_thrown_if_the_encryption_bundle_is_not_used(): void
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
                    ApplicationLayerBundle::class,
                ],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('snicco/encryption-bundle');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_the_cli_bundle_is_not_used(): void
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
                    ApplicationLayerBundle::class,
                    EncryptionBundle::class,
                ],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('snicco/better-wp-cli-bundle');

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

        $this->assertTrue($kernel->usesBundle('snicco/fortress-bundle'));
    }

    /**
     * @test
     */
    public function that_the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/fortress.php'));

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('encryption.' . EncryptionOption::KEY_ASCII, DefuseEncryptor::randomAsciiKey());
        });

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/fortress.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/fortress.php';

        $this->assertSame(require dirname(__DIR__, 2) . '/config/fortress.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/fortress.php',
            '<?php return ' . var_export([
                FortressOption::MODULES => ['auth'],
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/fortress.php'));

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('encryption.' . EncryptionOption::KEY_ASCII, DefuseEncryptor::randomAsciiKey());
        });

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame([
            FortressOption::MODULES => ['auth'],
        ], require $this->directories->configDir() . '/fortress.php');
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/fortress.php'));

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('encryption.' . EncryptionOption::KEY_ASCII, DefuseEncryptor::randomAsciiKey());
        });

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/fortress.php'));
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures/test-app';
    }
}
