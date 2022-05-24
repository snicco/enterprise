<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests;

use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

abstract class AuthWebTestCase extends WebTestCase
{
    protected function createKernel(): callable
    {
        return fn (Environment $env): Kernel => new Kernel(
            new PimpleContainerAdapter(),
            $env,
            Directories::fromDefaults(__DIR__ . '/fixtures/test-app')
        );
    }

    protected function extensions(): array
    {
        return [];
    }
}
