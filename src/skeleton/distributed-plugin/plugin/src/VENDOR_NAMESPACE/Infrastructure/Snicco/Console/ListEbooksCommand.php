<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\Snicco\Console;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;

use function WP_CLI\Utils\format_items;

final class ListEbooksCommand extends Command
{
    protected static string $name = 'ebook list';

    private AvailableEbooks $available_ebooks;

    public function __construct(AvailableEbooks $available_ebooks)
    {
        $this->available_ebooks = $available_ebooks;
    }

    public function execute(Input $input, Output $output): int
    {
        $books = [];

        foreach ($this->available_ebooks->forCustomers() as $available_ebook) {
            $books[] = $available_ebook->asArray();
        }

        format_items('table', $books, ['title', 'description', 'price', 'id']);

        return 0;
    }
}
