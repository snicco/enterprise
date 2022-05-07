<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function in_array;

final class UserRole implements Condition
{
    private string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    public function isTruthy(Context $context): bool
    {
        $user = $context->user();

        return in_array($this->role, $user->roles, true);
    }
}
