<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function user_can;

final class UserCan implements Condition
{
    private string $cap;

    public function __construct(string $cap)
    {
        $this->cap = $cap;
    }

    public function isTruthy(Context $context): bool
    {
        $user = $context->user();

        return user_can($user, $this->cap);
    }
}
