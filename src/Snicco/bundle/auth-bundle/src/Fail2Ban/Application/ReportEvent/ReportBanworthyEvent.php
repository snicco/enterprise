<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban\Application\ReportEvent;

/**
 * @psalm-immutable
 */
final class ReportBanworthyEvent
{
    
    public string $message;
    public int $priority;
    public string $ip_address;
    
    public function __construct(string $message, int $priority, string $ip_address) {
        $this->message = $message;
        $this->priority = $priority;
        $this->ip_address = $ip_address;
    }
    
}