<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\Event;

use WP_User;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 */
final class WPAuthenticate2FaChallengeRedirect implements Event
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