<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Arr;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class IsHeartbeat implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return 'heartbeat' === Arr::get($context->post(), 'action');
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
