<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure;

use Defuse\Crypto\Key;
use PasswordHash;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Enterprise\Bundle\Fortress\Password\Domain\PasswordPolicy;
use Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure\MappedEvent\ResettingPassword;
use Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure\MappedEvent\UpdatingUserInAdminArea;
use Snicco\Enterprise\Bundle\Fortress\Password\Infrastructure\Pluggable\PasswordPluggable;
use Snicco\Enterprise\Bundle\Fortress\Shared\Infrastructure\FortressModule;

use const ABSPATH;
use const WPINC;

final class PasswordModule extends FortressModule
{
    /**
     * @var string
     */
    public const NAME = 'password';

    public function name(): string
    {
        return self::NAME;
    }

    public function register(Kernel $kernel): void
    {
        $c = $kernel->container();
        $config = $kernel->config();

        $c->shared(PasswordEventHandler::class, fn (): PasswordEventHandler => new PasswordEventHandler(
            new PasswordPolicy(),
            $config->getListOfStrings('fortress.password.' . PasswordModuleOption::PASSWORD_POLICY_EXCLUDED_ROLES)
        ));
    }

    public function boot(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();

        $this->bootPasswordPluggable($container, $config);
        $this->bootPasswordPolicy($container);
    }

    private function bootPasswordPluggable(DIContainer $container, ReadOnlyConfig $config): void
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
                Key::loadFromAsciiSafeString(
                    $config->getString(
                        'encryption.' . EncryptionOption::KEY_ASCII
                    )
                ),
                $wp_hasher
            )
        );

        require_once __DIR__ . '/Pluggable/password-pluggable.php';
    }

    private function bootPasswordPolicy(DIContainer $container): void
    {
        $event_dispatcher = $container->make(EventDispatcher::class);
        $event_mapper = $container->make(EventMapper::class);

        $event_dispatcher->subscribe(PasswordEventHandler::class);

        $event_mapper->map('user_profile_update_errors', UpdatingUserInAdminArea::class);
        $event_mapper->map('validate_password_reset', ResettingPassword::class);
    }
}
