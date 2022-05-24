<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password\Infrastructure\Pluggable;

use LogicException;
use Snicco\Enterprise\Bundle\Auth\Password\Infrastructure\SecureWPPasswords;

/**
 * @codeCoverageIgnore
 */
final class PasswordPluggable
{
    private static ?SecureWPPasswords $instance = null;

    public static function set(SecureWPPasswords $secure_passwords): void
    {
        self::$instance = $secure_passwords;
    }

    public static function securePasswords(): SecureWPPasswords
    {
        if (null === self::$instance) {
            throw new LogicException('No instance of ' . SecureWPPasswords::class . ' has been set yet.');
        }

        return self::$instance;
    }
}
