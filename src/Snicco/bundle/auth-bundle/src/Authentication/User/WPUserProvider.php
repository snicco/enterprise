<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\User;

use WP_User;

use function get_user_by;
use function sprintf;
use function wp_check_password;

final class WPUserProvider implements UserProvider
{
    public function getUserByIdentifier(string $identifier): WP_User
    {
        $user = get_user_by('email', $identifier);

        if ($user instanceof WP_User) {
            return $user;
        }

        $user = get_user_by('login', $identifier);

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
        $res = wp_check_password($plain_text_password, $user->user_pass, $user->ID);
        if ($res) {
            return;
        }

        throw new InvalidPassword(sprintf('Invalid password [%s] for user %s.', $plain_text_password, $user->ID));
    }
}
