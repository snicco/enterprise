<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Normalizer;

final class Assert
{
    public static function canBeNormalized(Condition $condition): void
    {
        \PHPUnit\Framework\Assert::assertEquals($condition, Normalizer::denormalize(Normalizer::normalize($condition)));
    }
}
