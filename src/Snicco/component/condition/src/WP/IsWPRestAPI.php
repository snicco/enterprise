<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function ltrim;
use function rest_get_url_prefix;

final class IsWPRestAPI implements Condition
{
    public function isTruthy(Context $context): bool
    {
        $rest_prefix = '/' . ltrim(rest_get_url_prefix());

        return Str::startsWith($context->path(), $rest_prefix);
    }

    public function toArray(): array
    {
        return [self::class, []];
    }
}
