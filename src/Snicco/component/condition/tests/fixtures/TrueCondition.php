<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\fixtures;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class TrueCondition implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return true;
    }
}
