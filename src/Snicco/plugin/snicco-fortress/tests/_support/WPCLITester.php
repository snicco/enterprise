<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests;

use Codeception\Actor;
use Snicco\Enterprise\Fortress\Tests\_generated\WPCLITesterActions;
use Webmozart\Assert\Assert;

/**
 * Inherited Methods.
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
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
