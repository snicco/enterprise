<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

use function wp_set_auth_cookie;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Fortress
 *
 * @see wp_set_auth_cookie()
 */
final class SetLoginCookie implements MappedHook
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @psalm-readonly
     */
    public string $logged_in_cookie;

    /**
     * @psalm-readonly
     */
    public int $cookie_expiration;

    /**
     * @psalm-readonly
     */
    public int $session_expiration;

    /**
     * @psalm-readonly
     */
    public int $user_id;

    public function __construct(
        string $logged_in_cookie,
        int $cookie_expiration,
        int $session_expiration,
        int $user_id
    ) {
        $this->logged_in_cookie = $logged_in_cookie;
        $this->cookie_expiration = $cookie_expiration;
        $this->session_expiration = $session_expiration;
        $this->user_id = $user_id;
    }

    public function userWantsToBeRemembered(): bool
    {
        return 0 !== $this->cookie_expiration;
    }

    public function shouldDispatch(): bool
    {
        return true;
    }
}
