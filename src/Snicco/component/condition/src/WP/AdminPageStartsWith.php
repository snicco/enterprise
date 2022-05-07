<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function is_string;

final class AdminPageStartsWith implements Condition
{
    private string $vendor_prefix;

    public function __construct(string $vendor_prefix)
    {
        $this->vendor_prefix = $vendor_prefix;
    }

    public function isTruthy(Context $context): bool
    {
        if (! (new IsAdminArea())->isTruthy($context)) {
            return false;
        }

        $page_query_var = $context->queryVar('page');

        return is_string($page_query_var) && Str::startsWith($page_query_var, $this->vendor_prefix);
    }
}
