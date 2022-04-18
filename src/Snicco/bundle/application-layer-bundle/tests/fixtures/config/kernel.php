<?php

declare(strict_types=1);

use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [ApplicationLayerBundle::class],
    ],
];
