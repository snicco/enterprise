<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

interface CommandBus
{
    public function handle(object $command): void;
}
