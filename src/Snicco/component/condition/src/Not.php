<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

final class Not implements ContainingCondition
{
    private Condition $condition;

    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }

    public function isTruthy(Context $context): bool
    {
        return ! $this->condition->isTruthy($context);
    }

    public function toArray(): array
    {
        return [self::class, $this->condition->toArray()];
    }
}
