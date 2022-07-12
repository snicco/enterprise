<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

final class Any implements Condition
{
    /**
     * @var Condition[]
     */
    private array $conditions = [];

    /**
     * @param Condition[] $conditions
     */
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    public function isTruthy(Context $context): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->isTruthy($context)) {
                return true;
            }
        }

        return false;
    }
}
