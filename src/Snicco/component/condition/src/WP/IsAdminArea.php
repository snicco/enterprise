<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function is_admin;

final class IsAdminArea implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return is_admin();
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
