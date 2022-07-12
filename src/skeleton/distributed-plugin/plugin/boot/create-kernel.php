<?php

declare(strict_types=1);

use Pimple\Container;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use VENDOR_NAMESPACE\Infrastructure\Snicco\ConfigCacheWithRuntimeChecks;

return function (Environment $env): Kernel {
    /*
     * The mu-plugin uses pimple/pimple by default as a dependency
     * injection container.
     *
     * Pimple is the fastest DI container and has no dependencies.
     *
     * However, pimple does not perform automatic auto-wiring of
     * dependencies as the laravel container or symfony container does.
     *
     * If you want auto-wiring you can use the snicco/illuminate-container-bridge.
     *
     */
    $container = new PimpleContainerAdapter(new Container());

    $base_dir = \dirname(__DIR__);

    if ($env->isProduction() || $env->isStaging()) {
        $cache = new ConfigCacheWithRuntimeChecks();
    } else {
        $cache = null;
    }

    return new Kernel(
        $container,
        $env,
        new Directories(
            $base_dir,
            $base_dir . '/src/VENDOR_NAMESPACE/Infrastructure/Snicco/config',
            $base_dir . '/var/cache',
            $base_dir . '/var/log'
        ),
        $cache
    );
};
