<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\User;

use WP_User;

interface UserProvider
{
    public function getUserByIdentifier(string $identifier): WP_User;

    /**
     * @throws InvalidPassword
     */
    public function validatePassword(string $plain_text_password, WP_User $user): void;
}
