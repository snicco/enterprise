<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use WP_User;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Fortress
 */
final class WPAuthenticateChallengeRedirectShutdownPHP implements Event
{
    use ClassAsName;
    use ClassAsPayload;

    public bool $do_shutdown = true;

    /**
     * @psalm-readonly
     */
    public WP_User $user;

    /**
     * @psalm-readonly
     */
    public string $redirect_url;

    public function __construct(WP_User $user, string $redirect_url)
    {
        $this->user = $user;
        $this->redirect_url = $redirect_url;
    }
}
