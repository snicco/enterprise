<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Domain\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class AllowWeakAuthenticationForIdleSession implements Event, ExposeToWP
{
    use ClassAsPayload;
    use ClassAsName;

    /**
     * @readonly
     */
    public int $user_id;

    /**
     * @readonly
     */
    public int $seconds_without_activity;

    /**
     * @readonly
     */
    public bool $default;

    public bool $allow = false;

    public function __construct(int $user_id, int $seconds_without_activity, bool $default)
    {
        $this->user_id = $user_id;
        $this->seconds_without_activity = $seconds_without_activity;
        $this->default = $default;
        $this->allow = $default;
    }
}
