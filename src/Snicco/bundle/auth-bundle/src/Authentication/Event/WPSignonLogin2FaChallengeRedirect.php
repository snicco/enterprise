<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\Event;

use WP_User;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

final class WPSignonLogin2FaChallengeRedirect implements Event
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public bool $do_redirect = true;
    
    /**
     * @psalm-readonly
     */
    public WP_User $user;
    
    public function __construct(WP_User $user)
    {
        $this->user = $user;
    }
    
}