<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\AuthBundle;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [
            HttpRoutingBundle::class,
            BetterWPHooksBundle::class,
            BetterWPDBBundle::class,
            AuthBundle::class,
        ],
    ],
];
