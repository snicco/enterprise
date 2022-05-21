<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban\Event;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\BannableEvent;

use const LOG_WARNING;

final class WPLoginFailed implements MappedHook, BannableEvent
{
    use ClassAsName;
    use ClassAsPayload;

    private string $user_name;

    public function __construct(string $user_name)
    {
        $this->user_name = $user_name;
    }

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
        return 'WordPress login failed for user ' . $this->user_name;
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
