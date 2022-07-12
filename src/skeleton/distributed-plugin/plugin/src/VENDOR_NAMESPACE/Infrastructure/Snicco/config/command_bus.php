<?php

declare(strict_types=1);

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Logger\LoggerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBusOption;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbookService;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbookService;

return [
    // A list of all classes that handle at least one command via the command bus.
    CommandBusOption::APPLICATION_SERVICES => [CreateEbookService::class, ArchiveEbookService::class],

    // A list of classes implementation League\Tactician\MiddlewareInterface.
    CommandBusOption::MIDDLEWARE => [
        LockingMiddleware::class,
        LoggerMiddleware::class,
        CommandHandlerMiddleware::class,
    ],
];
