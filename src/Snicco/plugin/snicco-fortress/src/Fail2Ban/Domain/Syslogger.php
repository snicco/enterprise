<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Fail2Ban\Domain;

interface Syslogger
{
    /**
     * Open a connection to the system logger.
     */
    public function open(string $prefix, int $flags, int $facility): bool;

    /**
     * Log an entry to the system logger.
     */
    public function log(int $priority, string $message): bool;

    /**
     * Close a connection to the system logger.
     */
    public function close(): bool;
}
