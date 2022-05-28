<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Session\Domain;

use RuntimeException;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\AllowWeakAuthenticationForIdleSession;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionIdleTimeout;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionRotationTimeout;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Exception\InvalidSessionToken;

use function bin2hex;
use function hash;
use function is_string;
use function random_bytes;

final class SessionManager
{
    private EventDispatcher $event_dispatcher;

    private TimeoutConfig $timeout_config;

    private Clock $clock;

    private SessionRepository $session_repo;

    /**
     * @var array<string,string>
     */
    private array $rotated_sessions = [];

    public function __construct(
        EventDispatcher $event_dispatcher,
        TimeoutConfig $timeout_config,
        SessionRepository $session_repo,
        Clock $clock = null
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->timeout_config = $timeout_config;
        $this->session_repo = $session_repo;
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    /**
     * @throws InvalidSessionToken
     */
    public function getSession(string $hashed_token): AuthSession
    {
        $hashed_token = $this->withReferenceToRotatedToken($hashed_token);

        $session = $this->session_repo->getSession($hashed_token);

        $session = $this->applyIdleConfig($session);

        return $this->applyRotationConfig($session);
    }

    public function save(AuthSession $session): void
    {
        $session = $session->withToken(
            $this->withReferenceToRotatedToken($session->hashedToken())
        );

        $this->session_repo->save($session);
    }

    public function destroyAllSessionsForAllUsers(): void
    {
        $this->session_repo->destroyAll();
    }

    public function gc(): void
    {
        $this->session_repo->gc();
    }

    /**
     * @return array<string,array{
     *     last_activity: int,
     *     expires_at: int,
     *     last_rotation: int,
     *     data: array
     * }> Keys are the hashes_tokens
     */
    public function getAllForUser(int $user_id): array
    {
        return $this->session_repo->getAllForUser($user_id);
    }

    public function delete(string $hashed_token): void
    {
        $this->session_repo->delete($this->withReferenceToRotatedToken($hashed_token));
    }

    public function destroyOtherSessionsForUser(int $user_id, string $hashed_token): void
    {
        $this->session_repo->destroyOtherSessionsForUser($user_id, $hashed_token);
    }

    public function destroyAllSessionsForUser(int $user_id): void
    {
        $this->session_repo->destroyAllSessionsForUser($user_id);
    }

    public function updateActivity(string $token_plain): void
    {
        $this->session_repo->updateActivity(
            $this->withReferenceToRotatedToken($this->hashToken($token_plain))
        );
    }

    private function hashToken(string $token_plain): string
    {
        // The hash algo must always be the same as in WP_Session_Tokens
        $token_hashed = hash('sha256', $token_plain);

        // @codeCoverageIgnoreStart
        if (! is_string($token_hashed)) {
            throw new RuntimeException('Could not hash new session token');
        }

        // @codeCoverageIgnoreEnd

        return $token_hashed;
    }

    private function applyIdleConfig(AuthSession $session): AuthSession
    {
        $seconds_without_activity = $this->clock->currentTimestamp() - $session->lastActivity();

        $idle_timeout = $this->event_dispatcher->dispatch(
            new SessionIdleTimeout(
                $session->userId(),
                $this->timeout_config->default_idle_timeout,
                $this->timeout_config->default_idle_timeout,
                $seconds_without_activity
            )
        )->idle_timeout_in_seconds;

        $is_idle = $seconds_without_activity > $idle_timeout;

        if ($is_idle) {
            $event = new AllowWeakAuthenticationForIdleSession(
                $session->userId(),
                $seconds_without_activity,
                $this->timeout_config->allow_weekly_authenticated_sessions
            );

            $this->event_dispatcher->dispatch($event);

            if (! $event->allow) {
                $this->session_repo->delete($session->hashedToken());

                $this->event_dispatcher->dispatch(
                    new SessionWasIdle($session->hashedToken(), $session->userId())
                );

                throw InvalidSessionToken::forToken($session->hashedToken());
            }

            $session = $session->withWeakAuthentication();
        }

        return $session;
    }

    private function applyRotationConfig(AuthSession $session): AuthSession
    {
        $old_token = $session->hashedToken();
        $user_id = $session->userId();
        $last_rotated = $session->lastRotation();

        $seconds_without_rotation = $this->clock->currentTimestamp() - $last_rotated;

        $rotation_timeout = $this->event_dispatcher->dispatch(
            new SessionRotationTimeout(
                $user_id,
                $this->timeout_config->default_rotation_interval,
                $this->timeout_config->default_rotation_interval,
                $seconds_without_rotation
            )
        )->rotation_timeout_in_seconds;

        if ($seconds_without_rotation > $rotation_timeout) {
            $new_token_hashed = $this->hashToken($new_token_raw = $this->newToken());
            $ts = $this->clock->currentTimestamp();
            $this->session_repo->rotateToken($session->hashedToken(), $new_token_hashed, $ts);

            $session = new AuthSession(
                $new_token_hashed,
                $user_id,
                $session->lastActivity(),
                $ts,
                $session->data()
            );

            $this->event_dispatcher->dispatch(
                new SessionWasRotated(
                    $user_id,
                    $new_token_raw,
                    $old_token,
                    $session->expiresAt()
                )
            );

            $this->rotated_sessions[$old_token] = $new_token_hashed;
        }

        return $session;
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function withReferenceToRotatedToken(string $hashed_token): string
    {
        return $this->rotated_sessions[$hashed_token] ?? $hashed_token;
    }
}
