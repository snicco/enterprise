<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\BetterWPCLI\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPCLI\WPCLIApplication;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIOption;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

/**
 * @internal
 */
final class BetterWPCLIBundleTest extends Unit
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function that_the_alias_is_correct(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories, );

        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('snicco/better-wp-cli-bundle'));
    }

    /**
     * @test
     */
    public function that_the_wp_cli_application_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->boot();

        $this->assertCanBeResolved(WPCLIApplication::class, $kernel);
    }

    /**
     * @test
     */
    public function that_the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cli.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/better-wp-cli.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/better-wp-cli.php';

        $this->assertSame(require dirname(__DIR__, 2) . '/config/better-wp-cli.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/better-wp-cli.php',
            '<?php return ' . var_export([
                BetterWPCLIOption::NAME => 'foo',
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/better-wp-cli.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame([
            BetterWPCLIOption::NAME => 'foo',
        ], require $this->directories->configDir() . '/better-wp-cli.php');
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cli.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/better-wp-cli.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}
