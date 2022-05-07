<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

final class CallableCondition implements Condition
{
    /**
     * @var callable(Context=):bool
     */
    private $condition;

    /**
     * @param  callable(Context=):bool $condition
     */
    public function __construct(callable $condition)
    {
        $this->condition = $condition;
    }

    public function isTruthy(Context $context): bool
    {
        return ($this->condition)($context);
    }
}
