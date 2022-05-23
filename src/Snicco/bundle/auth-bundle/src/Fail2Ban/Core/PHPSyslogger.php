<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban\Core;

use function closelog;
use function openlog;
use function syslog;

final class PHPSyslogger implements Syslogger
{
    public function open(string $prefix, int $flags, int $facility): bool
    {
        return openlog($prefix, $flags, $facility);
    }

    public function log(int $priority, string $message): bool
    {
        return syslog($priority, $message);
    }

    public function close(): bool
    {
        return closelog();
    }
}
