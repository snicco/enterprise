<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests;

use Snicco\Component\Kernel\Kernel;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Kernel\ValueObject\Directories;

abstract class AuthWebTestCase extends WebTestCase
{
    
    protected function createKernel() :callable
    {
       return fn(Environment $env) => new Kernel(
           new PimpleContainerAdapter(),
           $env,
           Directories::fromDefaults(__DIR__.'/fixtures/test-app')
       );
    }
    
    protected function extensions() :array
    {
        return [];
    }
    
}