<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban\Infrastructure\MappedEvent;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Infrastructure\BanworthyEvent;

use const LOG_WARNING;

final class AuthCookieBadHash implements MappedHook, BanworthyEvent
{
    use ClassAsName;
    use ClassAsPayload;

    public function shouldDispatch(): bool
    {
        return true;
    }

    public function priority(): int
    {
        return LOG_WARNING;
    }

    public function message(): string
    {
        return 'Tampered auth cookie provided';
    }

    public function ip():?string
    {
        return null;
    }
}
