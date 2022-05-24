<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Fail2Ban\Infrastructure;

use Snicco\Enterprise\AuthBundle\Fail2Ban\Domain\Syslogger;

use function closelog;
use function openlog;
use function syslog;

/**
 * @codeCoverageIgnore
 */
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
