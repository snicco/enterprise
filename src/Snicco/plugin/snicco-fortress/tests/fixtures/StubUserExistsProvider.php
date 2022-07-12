<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\fixtures;

use BadMethodCallException;
use Snicco\Enterprise\Fortress\Auth\User\Domain\UserProvider;
use WP_User;

use function in_array;

final class StubUserExistsProvider implements UserProvider
{
    private array $users;

    /**
     * @param int[] $users
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function getUserByIdentifier(string $identifier): WP_User
    {
        throw new BadMethodCallException(__METHOD__);
    }

    public function exists(string $identifier): bool
    {
        return in_array((int) $identifier, $this->users, true);
    }
}
