<?php

declare(strict_types=1);

use Pimple\Container;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

return function (Environment $env): Kernel {
    $container = new PimpleContainerAdapter(new Container());

    $base_dir = \dirname(__DIR__);

    //if ($env->isProduction() || $env->isStaging()) {
    //    //$cache = new ConfigCacheWithRuntimeChecks();
    //} else {
    //    $cache = null;
    //}

    return new Kernel(
        $container,
        $env,
        new Directories(
            $base_dir,
            $base_dir . '/config',
            $base_dir . '/var/cache',
            $base_dir . '/var/log'
        )
    );
};
