<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

use Psr\Log\LoggerInterface;

/**
 * Marker interface to allow using a more customized logger for commands.
 */
interface CommandLogger extends LoggerInterface
{
}
