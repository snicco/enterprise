<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

use Codeception\Actor;
use Snicco\Enterprise\Component\Condition\_generated\End2EndTesterActions;

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
final class End2EndTester extends Actor
{
    use End2EndTesterActions;

    // Define custom actions here
}
