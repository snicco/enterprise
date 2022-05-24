<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\User\Domain;

use WP_User;
use Snicco\Enterprise\AuthBundle\Auth\User\Domain\InvalidPassword;

interface UserProvider
{
    public function getUserByIdentifier(string $identifier): WP_User;

    /**
     * @throws InvalidPassword
     */
    public function validatePassword(string $plain_text_password, WP_User $user): void;
}
