<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Domain;

final class TimeoutConfig
{
    public int $default_idle_timeout;

    public int $default_rotation_interval;

    public bool $allow_weekly_authenticated_sessions;

    public function __construct(
        int $default_idle_timeout,
        int $default_rotation_interval,
        bool $allow_weekly_authenticated_sessions = false
    ) {
        $this->default_idle_timeout = $default_idle_timeout;
        $this->default_rotation_interval = $default_rotation_interval;
        $this->allow_weekly_authenticated_sessions = $allow_weekly_authenticated_sessions;
    }
}
