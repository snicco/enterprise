<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Infrastructure\MappedEvent;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use WP_User;

use function time;
use function wp_doing_ajax;
use function wp_validate_auth_cookie;

/**
 * @see wp_validate_auth_cookie()
 */
final class AuthCookieValid implements MappedHook
{
    use ClassAsPayload;
    use ClassAsName;

    /**
     * @psalm-readonly
     */
    public string $raw_token;

    /**
     * @psalm-readonly
     */
    public int  $user_id;

    /**
     * @psalm-readonly
     */
    public int $timestamp;

    /**
     * @param array{
     *   token: string,
     * }  $cookie_elements
     */
    public function __construct(array $cookie_elements, WP_User $user)
    {
        $this->raw_token = $cookie_elements['token'];
        $this->user_id = $user->ID;
        $this->timestamp = time();
    }

    public function shouldDispatch(): bool
    {
        if (! wp_doing_ajax()) {
            return true;
        }

        if (! isset($_REQUEST['action'])) {
            return true;
        }

        // This event should not dispatch for the heartbeat API that
        // continuously polls the server.
        return 'heartbeat' !== $_REQUEST['action'];
    }
}
