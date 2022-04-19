<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Console;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbook;

final class ArchiveEbookCommand extends Command
{
    protected static string $name = 'ebook archive';

    private CommandBus $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    public function execute(Input $input, Output $output): int
    {
        $id = $input->getArgument('ebook_id', '');

        $this->bus->handle(new ArchiveEbook($id));

        return self::SUCCESS;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with(
            new InputArgument('ebook_id', 'The id of the ebook that you want to archive')
        );
    }
}
