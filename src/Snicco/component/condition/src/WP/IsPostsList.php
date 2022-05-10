<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class IsPostsList implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return Str::endsWith($context->scriptName(), '/wp-admin/edit.php');
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
