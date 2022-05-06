<?php

declare(strict_types=1);

use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIOption;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ArchiveAllEbooksCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ArchiveEbookCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\CreateEbookCommand;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Console\ListEbooksCommand;

return [
    // The name of your wp-cli application. E.G. wp snicco some-command
    BetterWPCLIOption::NAME => 'VENDOR_SLUG',

    // An array of class names that implement Snicco\Component\BetterWPCLI\Command
    BetterWPCLIOption::COMMANDS => [
        CreateEbookCommand::class,
        ListEbooksCommand::class,
        ArchiveEbookCommand::class,
        ArchiveAllEbooksCommand::class,
    ],
];
