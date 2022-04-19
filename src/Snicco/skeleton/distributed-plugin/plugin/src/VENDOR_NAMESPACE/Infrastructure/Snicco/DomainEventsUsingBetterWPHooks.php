<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco;

use Snicco\Component\EventDispatcher\EventDispatcher;
use VENDOR_NAMESPACE\Application\DomainEvents;

final class DomainEventsUsingBetterWPHooks implements DomainEvents
{
    private EventDispatcher $event_dispatcher;

    public function __construct(EventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function dispatchAll(array $events): void
    {
        foreach ($events as $event) {
            $this->event_dispatcher->dispatch($event);
        }
    }
}
