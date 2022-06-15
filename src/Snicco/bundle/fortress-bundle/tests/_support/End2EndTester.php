<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests;

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
class End2EndTester extends \Codeception\Actor
{
    use _generated\End2EndTesterActions;

    // Define custom actions here
}
