<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class IsFrontend implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return '/index.php' === $context->scriptName();
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
