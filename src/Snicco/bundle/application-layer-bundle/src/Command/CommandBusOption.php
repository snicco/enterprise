<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Command;

final class CommandBusOption
{
    /**
     * @var string
     */
    public const APPLICATION_SERVICES = 'application_services';

    /**
     * @var string
     */
    public const MIDDLEWARE = 'middleware';
}
