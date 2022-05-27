<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\Event;

use WP_User;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

final class WPAuthenticateChallengeUser implements Event, ExposeToWP
{
    use ClassAsName;
    use ClassAsPayload;
    
    /**
     * @psalm-readonly
     */
    public WP_User $user;
    
    /**
     * Set this value to false in order to prevent creating the 2FA challenge.
     * The user will then be logged in normally.
     */
    public bool $challenge_user = true;
    
    public function __construct(WP_User $user) {
        $this->user = $user;
    }
    
}