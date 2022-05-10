<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

use function array_map;

final class Any implements AggregateCondition
{
    /**
     * @var Condition[]
     */
    private array $conditions;

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

    public function toArray(): array
    {
        $arrays = array_map(fn (Condition $condition): array => $condition->toArray(), $this->conditions);

        return [self::class, $arrays];
    }
}
