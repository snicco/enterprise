<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

final class CommandBus
{
    private \League\Tactician\CommandBus $bus;

    public function __construct(\League\Tactician\CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function handle(object $command): void
    {
        $this->bus->handle($command);
    }
}
