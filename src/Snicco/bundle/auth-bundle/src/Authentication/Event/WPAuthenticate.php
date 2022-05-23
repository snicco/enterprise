<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\Event;

use WP_User;
use WP_Error;
use LogicException;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\BetterWPHooks\EventMapping\MappedFilter;

final class WPAuthenticate implements MappedFilter
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    /**
     * @var WP_Error|WP_User|null
     */
    private $user;
    
    /**
     * @param  null|WP_User|WP_Error  $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
    
    public function user() :WP_User
    {
        // @codeCoverageIgnoreStart
        if ( ! $this->user instanceof WP_User) {
            throw new LogicException('This event should not have been dispatched.');
        }
        // @codeCoverageIgnoreEnd
        return $this->user;
    }
    
    public function shouldDispatch() :bool
    {
        return $this->user instanceof WP_User;
    }
    
    public function filterableAttribute()
    {
        return $this->user;
    }
    
}