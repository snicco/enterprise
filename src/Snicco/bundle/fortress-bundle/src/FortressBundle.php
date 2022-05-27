<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress;

use Defuse\Crypto\Key;
use RuntimeException;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\Config;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\Fortress\Auth\AuthModule;
use Snicco\Enterprise\Bundle\Fortress\Fail2Ban\Infrastructure\Fail2BanModule;
use Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure\PasswordModule;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionModule;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\AuthRouteLoadingOptions;

use function array_filter;
use function array_map;
use function in_array;

final class FortressBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/auth-bundle';

    /**
     * @var array<class-string<Module>>
     */
    private const MODULES = [
        SessionModule::class,
        PasswordModule::class,
        Fail2BanModule::class,
        AuthModule::class,
    ];

    /**
     * @var Module[]
     */
    private array $modules;

    /**
     * @var Module[]|null
     */
    private ?array $enabled_modules = null;

    public function __construct()
    {
        $this->modules = array_map(fn (string $class): Module => new $class(), self::MODULES);
    }

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->validateBundles($kernel);

        $config->setIfMissing('snicco_auth.modules', [
            PasswordModule::NAME,
            AuthModule::NAME,
            Fail2BanModule::NAME,
            SessionModule::NAME,
        ]);
        $config->setIfMissing('snicco_auth.path_prefix', '/auth');

        if ($kernel->env()->isTesting()) {
            $config->setIfMissing(
                'snicco_auth.encryption_secret',
                Key::createNewRandomKey()->saveToAsciiSafeString()
            );
        }

        foreach ($this->enabledModules($config) as $enabled_module) {
            $enabled_module->configure($config, $kernel);
        }
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
            fn (): AuthRouteLoadingOptions => new AuthRouteLoadingOptions(
                new DefaultRouteLoadingOptions(
                    $config->getString('routing.' . RoutingOption::API_PREFIX)
                ),
                $config->getString('snicco_auth.path_prefix')
            )
        );
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
     * @return Module[]
     */
    private function enabledModules(Config $config): array
    {
        if (null === $this->enabled_modules) {
            $enabled = $config->getListOfStrings('snicco_auth.modules');

            $this->enabled_modules = array_filter(
                $this->modules,
                fn (Module $module): bool => in_array($module->name(), $enabled, true)
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
    }
}
