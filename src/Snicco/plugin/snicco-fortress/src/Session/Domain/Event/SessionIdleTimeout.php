<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Domain\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;

final class SessionIdleTimeout implements Event, ExposeToWP
{
    use ClassAsPayload;
    use ClassAsName;

    /**
     * @psalm-readonly
     */
    public int $user_id;

    /**
     * @psalm-readonly
     */
    public int $default_timeout_in_seconds;

    /**
     * @psalm-readonly
     */
    public int $seconds_without_activity;

    public int $idle_timeout_in_seconds;

    public function __construct(
        int $user_id,
        int $original_timeout,
        int $idle_timeout,
        int $seconds_without_activity
    ) {
        $this->user_id = $user_id;
        $this->default_timeout_in_seconds = $original_timeout;
        $this->idle_timeout_in_seconds = $idle_timeout;
        $this->seconds_without_activity = $seconds_without_activity;
    }
}
