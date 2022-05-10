<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

/**
 * @template conditionArray as array{
 *     0: class-string<Condition>,
 *     1: list
 * }
 */
interface AggregateCondition extends Condition
{
    /**
     * @return array{
     *     0: class-string<$this>,
     *     1: conditionArray[]
     * }
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @see https://github.com/vimeo/psalm/issues/7945
     */
    public function toArray(): array;
}
