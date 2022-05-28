<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

/**
 * This event can be used to adjust the redirect_to and remember_me
 * parameters that are parsed from the server environment
 * before intercepting a successful external authentication attempt through
 * {@see wp_authenticate()}
 */
final class WPAuthenticateRedirectContext implements Event, ExposeToWP
{
    use ClassAsName;
    use ClassAsPayload;
    
    /**
     * @readonly
     */
    public ?string $initial_parsed_redirect;
    
    /**
     * @readonly
     */
    public ?bool $initial_parsed_remember_me;
    
    public ?string $redirect_to;
    
    public ?bool $remember_me;
    
    public function __construct(?string $parsed_redirect, ?bool $parsed_remember_me) {
        $this->initial_parsed_redirect = $parsed_redirect;
        $this->initial_parsed_remember_me = $parsed_remember_me;
        $this->redirect_to = $parsed_redirect;
        $this->remember_me = $parsed_remember_me;
    }
    
}