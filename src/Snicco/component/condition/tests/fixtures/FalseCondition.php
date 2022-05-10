<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\fixtures;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Component\Condition
 */
final class FalseCondition implements Condition
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
