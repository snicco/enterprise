<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Console;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveAllEbooks;

final class ArchiveAllEbooksCommand extends Command
{
    protected static string $name = 'ebook archive:all';

    private CommandBus $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function execute(Input $input, Output $output): int
    {
        $io = new SniccoStyle($input, $output);

        if (! $io->confirm('Are you sure that you want do proceed? All ebooks will be archived')) {
            $io->warning('Command aborted.');

            return self::SUCCESS;
        }

        $this->bus->handle(new ArchiveAllEbooks());

        return self::SUCCESS;
    }
}
