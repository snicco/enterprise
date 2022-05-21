<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban;

final class Fail2BanEntry implements BannableEvent
{
    private string  $message;

    private int     $priority;

    private ?string $ip;

    public function __construct(string $message, int $priority, string $ip = null)
    {
        $this->message = $message;
        $this->priority = $priority;
        $this->ip = $ip;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function ip(): string
    {
        return $this->ip ?? (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
