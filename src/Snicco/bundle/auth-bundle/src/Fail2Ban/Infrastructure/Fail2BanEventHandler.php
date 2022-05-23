<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban\Infrastructure;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Application\ReportEvent\ReportBanworthyEvent;

final class Fail2BanEventHandler implements EventSubscriber
{
    private CommandBus $bus;
    
    public function __construct(CommandBus $bus) {
        $this->bus = $bus;
    }
    
    public static function subscribedEvents() :array
    {
        return [
            BanworthyEvent::class => 'onBanworthyEvent'
        ];
    }
    
    public function onBanWorthyEvent(BanworthyEvent $event) :void
    {
        $ip = $event->ip();
        
        if(null === $ip) {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        }
        
        $this->bus->handle(new ReportBanworthyEvent(
            $event->message(),
            $event->priority(),
            $ip
        ));
    }
}