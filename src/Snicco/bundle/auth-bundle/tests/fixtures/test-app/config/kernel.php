<?php

declare(strict_types=1);

use Snicco\Component\Kernel\KernelOption;
use Snicco\Enterprise\Bundle\Auth\AuthBundle;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\ValueObject\Environment;

return [
  
    KernelOption::BUNDLES =>[
        Environment::ALL => [
            HttpRoutingBundle::class,
            BetterWPDBBundle::class,
            BetterWPHooksBundle::class,
            AuthBundle::class,
        ]
    ]
    
];
