<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Infrastructure;

use WP_Session_Tokens;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Exception\InvalidSessionToken;

use function array_map;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class WPAuthSessionTokens extends WP_Session_Tokens
{
    private static SessionManager $session_manager;

    /**
     * I hate having to do this with all my guts but there is no other way. This
     * class can only be hooked into WordPress using a filter that returns a
     * class name. We have no way to use our DI layer anywhere.
     */
    public static function setSessionManager(SessionManager $session_manager): void
    {
        self::$session_manager = $session_manager;
    }

    public static function drop_sessions(): void
    {
        self::$session_manager->destroyAllSessionsForAllUsers();
    }

    protected function get_sessions(): array
    {
        return array_map(
            fn(array $session) => $session['data'], self::$session_manager->getAllForUser($this->user_id)
        );
    }

    protected function get_session($verifier): ?array
    {
        try {
            return self::$session_manager->getSession($verifier)->data();
        } catch (InvalidSessionToken $e) {
        }

        return null;
    }

    protected function update_session($verifier, $session = null): void
    {
        if (null === $session) {
            self::$session_manager->delete($verifier);
            return;
        }

        $session = AuthSession::fromArrayDataForStorage($verifier, $this->user_id, $session);

        self::$session_manager->save($session);
    }

    protected function destroy_other_sessions($verifier): void
    {
        self::$session_manager->destroyOtherSessionsForUser($this->user_id, $verifier);
    }

    protected function destroy_all_sessions(): void
    {
        self::$session_manager->destroyAllSessionsForUser($this->user_id);
    }
}
