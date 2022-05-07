<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition;

interface Condition
{
    public function isTruthy(Context $context): bool;
}
