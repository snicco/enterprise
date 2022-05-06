<?php

declare(strict_types=1);

use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIOption;

return [
    // The name of your wp-cli application. E.G. wp snicco some-command
    BetterWPCLIOption::NAME => 'snicco',

    // An array of class names that implement Snicco\Component\BetterWPCLI\Command
    BetterWPCLIOption::COMMANDS => [],
];
