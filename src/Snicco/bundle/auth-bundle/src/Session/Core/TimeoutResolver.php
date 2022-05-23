<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Core;

use Snicco\Component\TestableClock\Clock;

use Snicco\Component\TestableClock\SystemClock;

final class TimeoutResolver
{
    /**
     * @var int
     */
    public const TOTALLY_IDLE = 1;

    /**
     * @var int
     */
    public const NOT_IDLE = -1;

    /**
     * @var int
     */
    public const WEEKLY_IDLE = 0;

    private int $default_idle_timeout;

    private int $default_rotation_interval;

    private Clock $clock;

    private bool $allow_weekly_authenticated_sessions;

    public function __construct(
        int $default_idle_timeout,
        int $default_rotation_interval,
        Clock $clock = null,
        bool $allow_weekly_authenticated_sessions = false
    ) {
        $this->default_idle_timeout = $default_idle_timeout;
        $this->default_rotation_interval = $default_rotation_interval;
        $this->clock = $clock ?: SystemClock::fromUTC();
        $this->allow_weekly_authenticated_sessions = $allow_weekly_authenticated_sessions;
    }

    /**
     * @return self::TOTALLY_IDLE|self::WEEKLY_IDLE|self::NOT_IDLE
     */
    public function idleStatus(int $last_activity_timestamp, int $user_id): int
    {
        $seconds_without_activity = $this->clock->currentTimestamp() - $last_activity_timestamp;

        $is_idle = $seconds_without_activity > $this->default_idle_timeout;

        if (! $is_idle) {
            return self::NOT_IDLE;
        }

        return $this->allow_weekly_authenticated_sessions
            ? self::WEEKLY_IDLE
            : self::TOTALLY_IDLE;
    }

    public function needsRotation(int $last_rotation_timestamp, int $user_id): bool
    {
        $seconds_without_rotation = $this->clock->currentTimestamp() - $last_rotation_timestamp;

        return $seconds_without_rotation > $this->default_rotation_interval;
    }
}
