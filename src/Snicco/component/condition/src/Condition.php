<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

interface Condition
{
    public function isTruthy(Context $context): bool;

    /**
     * @return array{
     *     0: class-string<$this>,
     *     1: list
     * }
     */
    public function toArray(): array;
}
