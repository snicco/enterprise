<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function wp_doing_ajax;

final class IsAdminAjax implements Condition
{
    public function isTruthy(Context $context): bool
    {
        return wp_doing_ajax();
    }
}
