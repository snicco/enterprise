<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Domain;

use Snicco\Enterprise\AuthBundle\Session\Domain\Exception\InvalidSessionToken;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\AuthBundle
 */
interface SessionRepository
{
    public function save(AuthSession $session): void;

    public function delete(string $hashed_token): void;

    /**
     * @throws InvalidSessionToken
     */
    public function getSession(string $hashed_token): AuthSession;

    /**
     * @return array<string,array{
     *     last_activity: int,
     *     expires_at: int,
     *     last_rotation: int,
     *     data: array
     * }> Keys are the hashes_tokens
     */
    public function getAllForUser(int $user_id): array;

    public function destroyOtherSessionsForUser(int $user_id, string $hashed_token_to_keep): void;

    public function destroyAllSessionsForUser(int $user_id): void;

    public function destroyAll(): void;

    /**
     * @throws InvalidSessionToken
     */
    public function updateActivity(string $hashed_token): void;

    /**
     * @throws InvalidSessionToken
     */
    public function rotateToken(string $hashed_token_old, string $hashed_token_new, int $current_timestamp): void;

    /**
     * Destroy all expired sessions.
     */
    public function gc(): void;
}
