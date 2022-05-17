<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use WP_Session_Tokens;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class WPAuthSessions extends WP_Session_Tokens
{
    private static SessionRepository $session_repository;

    /**
     * I hate having to do this with all my guts but there is no other way. This
     * class can only be hooked into WordPress using a filter that returns a
     * class name. We have no way to use our DI layer anywhere.
     */
    public static function setSessionRepository(SessionRepository $session_repository): void
    {
        self::$session_repository = $session_repository;
    }

    public static function drop_sessions(): void
    {
        self::$session_repository->destroyAllSessions();
    }

    protected function get_sessions(): array
    {
        return self::$session_repository->getSessions($this->user_id);
    }

    protected function get_session($verifier): ?array
    {
        return self::$session_repository->getSession($this->user_id, $verifier);
    }

    protected function update_session($verifier, $session = null): void
    {
        if (null === $session) {
            self::$session_repository->delete($verifier);

            return;
        }

        self::$session_repository->update($this->user_id, $verifier, $session);
    }

    protected function destroy_other_sessions($verifier): void
    {
        self::$session_repository->destroyOtherSessionsForUser($this->user_id, $verifier);
    }

    protected function destroy_all_sessions(): void
    {
        self::$session_repository->destroyAllSessionsForUser($this->user_id);
    }
}
