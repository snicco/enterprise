<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

final class Unless implements Condition
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
}
