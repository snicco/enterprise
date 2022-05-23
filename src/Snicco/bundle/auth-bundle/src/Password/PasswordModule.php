<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password;

use Defuse\Crypto\Key;
use PasswordHash;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Enterprise\Bundle\Auth\AuthModule;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Enterprise\Bundle\Auth\Password\Core\SecureWPPasswords;
use Snicco\Enterprise\Bundle\Auth\Password\Core\PasswordEventHandler;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Event\ResettingPassword;
use Snicco\Enterprise\Bundle\Auth\Password\Core\Event\UpdatingUserInAdminArea;

use const \WPINC;
use const \ABSPATH;

final class PasswordModule extends AuthModule
{
    public function name(): string
    {
        return 'password';
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->setIfMissing('snicco_auth.password.password_policy_excluded_roles', []);
    }

    public function register(Kernel $kernel): void
    {
        $c = $kernel->container();
        $config = $kernel->config();

        $c->shared(PasswordEventHandler::class, fn (): PasswordEventHandler => new PasswordEventHandler(
            $config->getListOfStrings('snicco_auth.password.password_policy_excluded_roles')
        ));
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $this->passwordPluggable($container, $config);
        $this->passwordPolicy($container);
    }

    private function passwordPluggable(DIContainer $container, ReadOnlyConfig $config): void
    {
        $wp_hasher = $GLOBALS['wp_hasher'] ?? null;

        if (! $wp_hasher instanceof PasswordHash) {
            /** @psalm-suppress MissingFile */
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = new PasswordHash(8, true);
        }

        PasswordPluggable::set(
            new SecureWPPasswords(
                $container[BetterWPDB::class],
                Key::loadFromAsciiSafeString($config->getString('snicco_auth.encryption_secret')),
                $wp_hasher
            )
        );

        require_once __DIR__ . '/password-pluggable.php';
    }

    private function passwordPolicy(DIContainer $container): void
    {
        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_dispatcher->subscribe(PasswordEventHandler::class);

        $event_mapper->map('user_profile_update_errors', UpdatingUserInAdminArea::class);
        $event_mapper->map('validate_password_reset', ResettingPassword::class);
    }
}
