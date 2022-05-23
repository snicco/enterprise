<?php

declare(strict_types=1);

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Logger\LoggerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBusOption;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\Middleware\BetterWPDBTransaction;

return [
    // A list of all classes that handle at least one command via the command bus.
    CommandBusOption::APPLICATION_SERVICES => [],

    // A list of classes implementation League\Tactician\MiddlewareInterface.
    CommandBusOption::MIDDLEWARE => [
        LockingMiddleware::class,
        LoggerMiddleware::class,
        //BetterWPDBTransaction::class,
        CommandHandlerMiddleware::class,
    ],
];
