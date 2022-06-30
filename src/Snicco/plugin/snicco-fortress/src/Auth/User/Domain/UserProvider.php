<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\User\Domain;

use WP_User;

interface UserProvider
{
    /**
     * @throws UserNotFound
     */
    public function getUserByIdentifier(string $identifier): WP_User;

    public function exists(string $identifier): bool;
}
