<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Fail2Ban\Application;

use Snicco\Enterprise\AuthBundle\Fail2Ban\Domain\Syslogger;
use Snicco\Enterprise\AuthBundle\Fail2Ban\Application\ReportEvent\ReportBanworthyEvent;

use function sprintf;

final class Fail2BanCommandHandler
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

    public function __invoke(ReportBanworthyEvent $command) :void
    {
        $this->syslogger->open(
            $this->config['daemon'],
            $this->config['flags'],
            $this->config['facility']
        );
    
        $this->syslogger->log($command->priority, $this->formatMessage($command));
    
        $this->syslogger->close();
    }

    private function formatMessage(ReportBanworthyEvent $command): string
    {
        return sprintf('%s from %s', $command->message, $command->ip_address);
    }
}
