<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress;

use RuntimeException;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\Config;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIBundle;
use Snicco\Enterprise\Bundle\Fortress\Auth\AuthModule;
use Snicco\Enterprise\Bundle\Fortress\Fail2Ban\Infrastructure\Fail2BanModule;
use Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure\PasswordModule;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionModule;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\AcceptsJsonOnly;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\FortressModule;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\FortressRouteLoadingOptions;
use Snicco\Middleware\Negotiation\NegotiateContent;

use function array_filter;
use function array_map;
use function copy;
use function dirname;
use function get_locale;
use function in_array;
use function is_file;
use function sprintf;

final class FortressBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/fortress-bundle';

    /**
     * @var array<class-string<FortressModule>>
     */
    private const MODULES = [
        SessionModule::class,
        PasswordModule::class,
        Fail2BanModule::class,
        AuthModule::class,
    ];

    /**
     * @var FortressModule[]
     */
    private array $modules;

    /**
     * @var FortressModule[]|null
     */
    private ?array $enabled_modules = null;

    public function __construct()
    {
        $this->modules = array_map(fn (string $class): FortressModule => new $class(), self::MODULES);
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/fortress.php');

        $this->validateBundles($kernel);

        if ($kernel->env()->isTesting()) {
            $config->setIfMissing(
                'encryption.' . EncryptionOption::KEY_ASCII,
                DefuseEncryptor::randomAsciiKey()
            );
        }

        foreach ($this->enabledModules($config) as $enabled_module) {
            $enabled_module->configure($config, $kernel);
        }

        $this->copyConfiguration($kernel);
    }

    public function register(Kernel $kernel): void
    {
        $this->validateBundles($kernel);

        foreach ($this->enabledModules($kernel->config()) as $enabled_module) {
            $enabled_module->register($kernel);
        }

        $container = $kernel->container();
        $config = $kernel->config();

        $container->shared(
            RouteLoadingOptions::class,
            fn (): FortressRouteLoadingOptions => new FortressRouteLoadingOptions(
                new DefaultRouteLoadingOptions(
                    $config->getString('routing.' . RoutingOption::API_PREFIX)
                ),
                $config->getString('fortress.' . FortressOption::ROUTE_PATH_PREFIX)
            )
        );

        $container->shared(
            Clock::class,
            fn (): Clock => $container[TestClock::class] ?? SystemClock::fromUTC()
        );

        $container->shared(AcceptsJsonOnly::class, function () use ($container): AcceptsJsonOnly {
            $negotiate_content = new NegotiateContent([get_locale()], [
                'json' => [
                    'extension' => ['json'],
                    'mime-type' => ['application/json'],
                    'charset' => true,
                ],
            ]);
            $negotiate_content->setContainer($container);

            return new AcceptsJsonOnly($negotiate_content);
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
        foreach ($this->enabledModules($kernel->config()) as $enabled_module) {
            $enabled_module->boot($kernel);
        }
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    /**
     * @return FortressModule[]
     */
    private function enabledModules(Config $config): array
    {
        if (null === $this->enabled_modules) {
            $enabled = $config->getListOfStrings('fortress.' . FortressOption::MODULES);

            $this->enabled_modules = array_filter(
                $this->modules,
                fn (FortressModule $module): bool => in_array($module->name(), $enabled, true)
            );
        }

        return $this->enabled_modules;
    }

    private function validateBundles(Kernel $kernel): void
    {
        if (! $kernel->usesBundle(HttpRoutingBundle::ALIAS)) {
            throw new RuntimeException(self::ALIAS . ' needs the ' . HttpRoutingBundle::ALIAS . ' to run.');
        }

        if (! $kernel->usesBundle(ApplicationLayerBundle::ALIAS)) {
            throw new RuntimeException(self::ALIAS . ' needs the ' . ApplicationLayerBundle::ALIAS . ' to run.');
        }

        if (! $kernel->usesBundle(EncryptionBundle::ALIAS)) {
            throw new RuntimeException(self::ALIAS . ' needs the ' . EncryptionBundle::ALIAS . ' to run.');
        }

        if ($kernel->usesBundle(BetterWPCLIBundle::ALIAS)) {
            return;
        }

        if (! $kernel->env()->isCli()) {
            return;
        }

        throw new RuntimeException(self::ALIAS . ' needs the ' . BetterWPCLIBundle::ALIAS . ' to run.');
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/fortress.php';

        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/fortress.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Could not copy the default templating config to destination [%s]', $destination)
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
