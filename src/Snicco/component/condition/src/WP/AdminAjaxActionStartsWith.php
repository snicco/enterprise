<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function is_string;

final class AdminAjaxActionStartsWith implements Condition
{
    private string $action;

    public function __construct(string $action)
    {
        $this->action = $action;
    }

    public function isTruthy(Context $context): bool
    {
        $admin_ajax = new IsAdminAjax();
        if (! $admin_ajax->isTruthy($context)) {
            return false;
        }

        $query_var = $context->queryVar('action');

        if (is_string($query_var) && '' !== $query_var && Str::startsWith($query_var, $this->action)) {
            return true;
        }

        /** @var string|string[] $post */
        $post = Arr::get($context->post(), 'action');

        return is_string($post) && '' !== $post && Str::startsWith($post, $this->action);
    }
}
