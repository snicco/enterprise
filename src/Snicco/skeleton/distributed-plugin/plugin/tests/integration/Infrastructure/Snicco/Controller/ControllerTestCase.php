<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\integration\Infrastructure\Snicco\Controller;

use Snicco\Bundle\Testing\Functional\WebTestCase;

use function dirname;

abstract class ControllerTestCase extends WebTestCase
{
    protected function createKernel(): callable
    {
        return require dirname(__DIR__, 5) . '/boot/create-kernel.php';
    }

    protected function extensions(): array
    {
        return [];
    }
}
