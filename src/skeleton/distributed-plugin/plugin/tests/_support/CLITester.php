<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests;

use Codeception\Actor;
use VENDOR_NAMESPACE\Tests\_generated\CLITesterActions;

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
 * @SuppressWarnings(PHPMD)
 */
final class CLITester extends Actor
{
    use CLITesterActions;

    // Define custom actions here
}
