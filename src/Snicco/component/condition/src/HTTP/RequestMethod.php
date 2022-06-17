<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Component\StrArr\Arr;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;
use function in_array;

final class RequestMethod implements Condition
{
    /**
     * @var string[]
     */
    private array $methods = [];

    /**
     * @param string|string[] $methods
     */
    public function __construct($methods)
    {
        $this->methods = Arr::toArray($methods);
    }

    public function isTruthy(Context $context): bool
    {
        $request_method = $context->requestMethod();

        return in_array($request_method, $this->methods, true);
    }
}
