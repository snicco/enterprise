<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

abstract class AuthModule
{
    abstract public function name(): string;

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        // Do nothing
    }

    public function register(Kernel $kernel): void
    {
        // Do nothing
    }

    public function boot(Kernel $kernel): void
    {
        // Do nothing
    }
}
