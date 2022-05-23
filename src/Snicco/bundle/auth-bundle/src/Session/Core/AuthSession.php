<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Core;

use InvalidArgumentException;

use function is_int;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 */
final class AuthSession
{
    private string $hashed_token;

    private int $user_id;

    private array $data;

    private bool $fully_authenticated;

    private int $expires_at;

    /**
     * @param mixed[] $data
     */
    public function __construct(string $hashed_token, int $user_id, array $data)
    {
        // We don't need the expiration here, but if the user mistakenly unsets it
        // he will be logged out if stops using this bundle.
        if (! isset($data['expiration']) || ! is_int($data['expiration'])) {
            throw new InvalidArgumentException('The session data must contain an expiration timestamp.');
        }

        $this->hashed_token = $hashed_token;
        $this->user_id = $user_id;
        $this->data = $data;
        $this->fully_authenticated = isset($data['__snicco_fully_authenticated'])
                                     && true === $data['__snicco_fully_authenticated'];
        $this->expires_at = $data['expiration'];
    }

    public function hashedToken(): string
    {
        return $this->hashed_token;
    }

    public function userId(): int
    {
        return $this->user_id;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function fullyAuthenticated(): bool
    {
        return $this->fully_authenticated;
    }

    public function expiresAt(): int
    {
        return $this->expires_at;
    }
}
