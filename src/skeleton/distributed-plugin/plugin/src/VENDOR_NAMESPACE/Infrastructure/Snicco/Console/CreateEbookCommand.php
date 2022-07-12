<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Console;

use Ramsey\Uuid\Uuid;
use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use Snicco\Component\BetterWPCLI\Style\SniccoStyle;
use Snicco\Component\BetterWPCLI\Synopsis\InputArgument;
use Snicco\Component\BetterWPCLI\Synopsis\Synopsis;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbook;

final class CreateEbookCommand extends Command
{
    protected static string $name = 'ebook create';

    private CommandBus      $command_bus;

    public function __construct(CommandBus $command_bus)
    {
        $this->command_bus = $command_bus;
    }

    public function execute(Input $input, Output $output): int
    {
        $id = Uuid::uuid4()->toString();

        $this->command_bus->handle(
            new CreateEbook(
                $id,
                $input->getArgument('title', ''),
                $input->getArgument('description', ''),
                (int) $input->getArgument('price'),
            )
        );

        $io = new SniccoStyle($input, $output);

        $io->success(['Ebook created']);
        $output->writeln($id);

        return Command::SUCCESS;
    }

    public static function synopsis(): Synopsis
    {
        return parent::synopsis()->with([
            new InputArgument('title', 'The ebook title'),
            new InputArgument('price', 'The ebook price in cents'),
            new InputArgument('description', 'The ebook description'),
        ]);
    }
}
