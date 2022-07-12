<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\User\Infrastructure;

use Snicco\Enterprise\Fortress\Auth\User\Domain\InvalidPassword;
use Snicco\Enterprise\Fortress\Auth\User\Domain\PasswordAuthenticationUserProvider;
use Snicco\Enterprise\Fortress\Auth\User\Domain\UserNotFound;
use WP_User;

use function get_user_by;
use function sprintf;
use function wp_check_password;

final class UserProviderWPDB implements PasswordAuthenticationUserProvider
{
    public function getUserByIdentifier(string $identifier): WP_User
    {
        $user = get_user_by('login', $identifier);

        if ($user instanceof WP_User) {
            return $user;
        }

        $user = get_user_by('email', $identifier);

        if ($user instanceof WP_User) {
            return $user;
        }

        $id = (int) $identifier;

        if (0 !== $id && ($user = get_user_by('id', $id))) {
            return $user;
        }

        throw new UserNotFound(sprintf(
            'No WordPress user can be found by email or login name [%s].',
            $identifier
        ));
    }

    public function validatePassword(string $plain_text_password, WP_User $user): void
    {
        $valid = wp_check_password($plain_text_password, $user->user_pass, $user->ID);
        if ($valid) {
            return;
        }

        throw new InvalidPassword(sprintf('Invalid password [%s] for user %s.', $plain_text_password, $user->ID));
    }

    public function exists(string $identifier): bool
    {
        try {
            return 0 !== $this->getUserByIdentifier($identifier)
                ->ID;
        } catch (UserNotFound $e) {
            return false;
        }
    }
}
