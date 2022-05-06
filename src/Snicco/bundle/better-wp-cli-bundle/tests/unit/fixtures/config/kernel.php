<?php

declare(strict_types=1);

use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIBundle;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [BetterWPCLIBundle::class],
    ],
];
