<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIBundle;
use Snicco\Enterprise\Fortress\FortressBundle;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [
            HttpRoutingBundle::class,
            BetterWPDBBundle::class,
            BetterWPHooksBundle::class,
            FortressBundle::class,
            EncryptionBundle::class,
            ApplicationLayerBundle::class,
            BetterWPCLIBundle::class,
        ],
    ],
];
