<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\fixtures;

use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Exception\InvalidSessionToken;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\SessionRepository;

use function array_filter;
use function array_map;

final class InMemorySessionRepository implements SessionRepository
{
    /**
     * @var array<string,AuthSession>
     */
    private array $sessions = [];

    private Clock $clock;

    public function __construct(Clock $clock = null)
    {
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    public function save(AuthSession $session): void
    {
        $this->sessions[$session->hashedToken()] = new AuthSession(
            $session->hashedToken(),
            $session->userId(),
            $this->clock->currentTimestamp(),
            $session->lastRotation(),
            $session->data()
        );
    }

    public function delete(string $hashed_token): void
    {
        if (! isset($this->sessions[$hashed_token])) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        unset($this->sessions[$hashed_token]);
    }

    public function getSession(string $hashed_token): AuthSession
    {
        $session = $this->sessions[$hashed_token] ?? null;

        if (! $session instanceof AuthSession) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        if ($this->isExpired($session)) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        return $session;
    }

    public function getAllForUser(int $user_id): array
    {
        $sessions = array_filter(
            $this->sessions,
            fn (AuthSession $session): bool => $session->userId() === $user_id
                                        && ! $this->isExpired($session)
        );

        return array_map(fn (AuthSession $session): array => [
            'expires_at' => $session->expiresAt(),
            'last_activity' => $session->lastActivity(),
            'last_rotation' => $session->lastRotation(),
            'data' => $session->data(),
        ], $sessions);
    }

    public function destroyOtherSessionsForUser(int $user_id, string $hashed_token_to_keep): void
    {
        $this->sessions = array_filter(
            $this->sessions,
            fn (AuthSession $session): bool => $session->userId() !== $user_id
                                        || $hashed_token_to_keep === $session->hashedToken()
        );
    }

    public function destroyAllSessionsForUser(int $user_id): void
    {
        $this->sessions = array_filter(
            $this->sessions,
            fn (AuthSession $session): bool => $session->userId() !== $user_id
        );
    }

    public function destroyAll(): void
    {
        $this->sessions = [];
    }

    public function updateActivity(string $hashed_token): void
    {
        $session = $this->sessions[$hashed_token] ?? null;

        if (! $session instanceof AuthSession) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        $this->sessions[$hashed_token] = new AuthSession(
            $session->hashedToken(),
            $session->userId(),
            $this->clock->currentTimestamp(),
            $session->lastRotation(),
            $session->data()
        );
    }

    public function rotateToken(string $hashed_token_old, string $hashed_token_new, int $current_timestamp): void
    {
        $old = $this->sessions[$hashed_token_old] ?? null;

        if (! $old instanceof AuthSession) {
            throw InvalidSessionToken::forToken($hashed_token_old);
        }

        $this->sessions[$hashed_token_new] = new AuthSession(
            $hashed_token_new,
            $old->userId(),
            $old->lastActivity(),
            $current_timestamp,
            $old->data()
        );

        unset($this->sessions[$hashed_token_old]);
    }

    public function gc(): void
    {
    }

    private function isExpired(AuthSession $session): bool
    {
        return $session->expiresAt() < $this->clock->currentTimestamp();
    }
}
