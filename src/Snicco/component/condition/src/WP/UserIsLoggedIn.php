<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class UserIsLoggedIn implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return $context->user()
            ->exists();
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
