<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\AuthBundle;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [
            AuthBundle::class,
            BetterWPHooksBundle::class,
            BetterWPDBBundle::class,
        ],
    ],
];
