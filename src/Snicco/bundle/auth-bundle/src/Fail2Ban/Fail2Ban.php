<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban;

use function sprintf;

final class Fail2Ban
{
    private Syslogger $syslogger;

    /**
     * @var array{daemon: string, flags: int, facility: int}
     */
    private array $config;

    /**
     * @param array{daemon: string, flags: int, facility: int} $config
     */
    public function __construct(Syslogger $syslogger, array $config)
    {
        $this->syslogger = $syslogger;
        $this->config = $config;
    }

    public function report(BannableEvent $event): void
    {
        $this->syslogger->open(
            $this->config['daemon'],
            $this->config['flags'],
            $this->config['facility']
        );

        $this->syslogger->log($event->priority(), $this->formatMessage($event));

        $this->syslogger->close();
    }

    private function formatMessage(BannableEvent $event): string
    {
        return sprintf('%s from %s', $event->message(), $event->ip());
    }
}
