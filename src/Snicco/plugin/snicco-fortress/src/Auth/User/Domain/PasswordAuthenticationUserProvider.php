<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\User\Domain;

use WP_User;

interface PasswordAuthenticationUserProvider extends UserProvider
{
    /**
     * @throws InvalidPassword
     */
    public function validatePassword(string $plain_text_password, WP_User $user): void;
}
