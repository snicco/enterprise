<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application;

interface DomainEvents
{
    /**
     * @param object[] $events
     */
    public function dispatchAll(array $events): void;
}
