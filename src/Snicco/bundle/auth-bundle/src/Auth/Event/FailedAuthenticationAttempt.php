<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Component\EventDispatcher\Event;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Infrastructure\BanworthyEvent;

use const LOG_WARNING;

abstract class FailedAuthenticationAttempt implements Event, ExposeToWP, BanworthyEvent
{
    use ClassAsPayload;
    use ClassAsName;

    private string $ip;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public function priority(): int
    {
        return LOG_WARNING;
    }

    public function ip():?string
    {
        return $this->ip;
    }
}
