<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Tests;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class AlwaysFalseCondition implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
