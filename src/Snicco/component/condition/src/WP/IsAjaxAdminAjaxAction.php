<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Arr;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class IsAjaxAdminAjaxAction implements Condition
{
    private string $action;

    public function __construct(string $action)
    {
        $this->action = $action;
    }

    public function isTruthy(Context $context): bool
    {
        if (! (new IsAdminAjax())->isTruthy($context)) {
            return false;
        }

        if ($this->action === $context->queryVar('action')) {
            return true;
        }

        return Arr::get($context->post(), 'action') === $this->action;
    }
}
