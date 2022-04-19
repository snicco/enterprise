<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\BetterWPCLI\Tests\unit\fixtures\Commands;

use Snicco\Component\BetterWPCLI\Command;
use Snicco\Component\BetterWPCLI\Input\Input;
use Snicco\Component\BetterWPCLI\Output\Output;

final class TestCommand extends Command
{
    public string $dependency;

    public function __construct(string $dependency)
    {
        $this->dependency = $dependency;
    }

    public function execute(Input $input, Output $output): int
    {
        return 0;
    }
}
