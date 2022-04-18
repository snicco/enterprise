<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

final class FakeCommandBus implements CommandBus
{
    /**
     * @var object[]
     *
     * @psalm-readonly-allow-private-mutation
     */
    public array $commands = [];

    public function handle(object $command): void
    {
        $this->commands[] = $command;
    }
}
