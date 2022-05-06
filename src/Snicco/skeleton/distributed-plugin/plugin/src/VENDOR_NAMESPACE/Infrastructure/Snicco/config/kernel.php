<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Debug\DebugBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Component\Kernel\KernelOption;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\ApplicationLayerBundle;
use Snicco\Enterprise\Bundle\BetterWPCLI\BetterWPCLIBundle;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Bootstrap\BindServices;
use VENDOR_NAMESPACE\Infrastructure\Snicco\Bootstrap\ConfigureBundles;
use VENDOR_NAMESPACE\Infrastructure\WordPress\IntegrateWithWordPress;

return [
    KernelOption::BUNDLES => [
        Environment::ALL => [
            HttpRoutingBundle::class,
            BetterWPHooksBundle::class,
            BetterWPDBBundle::class,
            TemplatingBundle::class,
            BetterWPCLIBundle::class,
            // This comes last so that other bundles can register command handlers.
            ApplicationLayerBundle::class,
        ],

        Environment::DEV => [DebugBundle::class],
    ],

    KernelOption::BOOTSTRAPPERS => [BindServices::class, ConfigureBundles::class, IntegrateWithWordPress::class],
];
