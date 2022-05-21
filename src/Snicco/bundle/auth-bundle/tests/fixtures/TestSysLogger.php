<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\fixtures;

use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Syslogger;
use function sprintf;

final class TestSysLogger implements Syslogger
{
    private array $opened_log = [];

    /**
     * @var string[]
     */
    private array $log_entries = [];

    private bool  $log_closed = false;

    public function open(string $prefix, int $flags, int $facility): bool
    {
        $this->opened_log = [
            'prefix' => $prefix,
            'flags' => $flags,
            'facility' => $facility,
        ];

        return true;
    }

    public function log(int $priority, string $message): bool
    {
        $this->log_entries[] = (string) $priority . '-' . $message;

        return true;
    }

    public function close(): bool
    {
        $this->log_closed = true;

        return $this->log_closed;
    }

    public function assertLogOpened(): void
    {
        PHPUnit::assertNotEmpty($this->opened_log, 'Log was not opened.');
    }

    public function assertLogOpenedWithPrefix(string $prefix): void
    {
        PHPUnit::assertSame(
            $prefix,
            $this->opened_log['prefix'],
            sprintf('Log not opened with prefix [%s].', $prefix)
        );
    }

    public function assertLogOpenedWithFlags(int $flags): void
    {
        PHPUnit::assertSame(
            $flags,
            $this->opened_log['flags'],
            sprintf('Log not opened with flags [%d].', $flags)
        );
    }

    public function assertLogOpenedForFacility(int $facility): void
    {
        PHPUnit::assertSame(
            $facility,
            $this->opened_log['facility'],
            sprintf('Log not opened with facility [%d].', $facility)
        );
    }

    public function assertLogEntry(int $priority, string $message): void
    {
        PHPUnit::assertContains((string) $priority . '-' . $message, $this->log_entries);
    }
}
