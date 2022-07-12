<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests;

use Codeception\Actor;
use Snicco\Enterprise\Fortress\Tests\_generated\WPCLITesterActions;
use Webmozart\Assert\Assert;

final class WPCLITester extends Actor
{
    use WPCLITesterActions;

    public function haveFortressActivated(): void
    {
        $this->cli(['plugin', 'activate', 'snicco-fortress']);
        $output = $this->grabLastShellOutput();
        Assert::contains($output, 'activated');
    }
}
