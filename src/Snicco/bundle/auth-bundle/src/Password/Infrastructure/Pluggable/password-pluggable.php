<?php

declare(strict_types=1);

use Snicco\Enterprise\Bundle\Auth\Password\Infrastructure\Pluggable\PasswordPluggable;

if (! \function_exists('wp_check_password')) {
    /**
     * @codeCoverageIgnore
     */
    function wp_check_password(string $plain_text_password, string $stored_hash, ?int $user_id = null): bool
    {
        $passwords = PasswordPluggable::securePasswords();

        return $passwords->check($plain_text_password, $stored_hash, $user_id);
    }
}

if (! \function_exists('wp_hash_password')) {
    /**
     * @codeCoverageIgnore
     */
    function wp_hash_password(string $plain_text_password): string
    {
        $passwords = PasswordPluggable::securePasswords();

        return $passwords->hash($plain_text_password);
    }
}

if (! \function_exists('wp_set_password')) {
    /**
     * @codeCoverageIgnore
     *
     * @psalm-suppress MissingReturnType False positive because of the php-stubs/wordpress-stubs file
     */
    function wp_set_password(string $plain_text_password, int $user_id): string
    {
        $passwords = PasswordPluggable::securePasswords();

        return $passwords->update($plain_text_password, $user_id);
    }
}
