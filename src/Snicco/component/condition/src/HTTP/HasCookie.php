<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Component\StrArr\Arr;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class HasCookie implements Condition
{
    private string $cookie_name;

    private ?string $cookie_value;

    public function __construct(string $cookie_name, ?string $cookie_value)
    {
        $this->cookie_name = $cookie_name;
        $this->cookie_value = $cookie_value;
    }

    public function isTruthy(Context $context): bool
    {
        if (null === $this->cookie_value) {
            return Arr::has($context->cookies(), $this->cookie_name);
        }

        return $this->cookie_value === Arr::get($context->cookies(), $this->cookie_name);
    }
}
