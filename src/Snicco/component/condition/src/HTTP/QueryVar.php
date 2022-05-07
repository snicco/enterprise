<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class QueryVar implements Condition
{
    /**
     * @var array<string,string>
     */
    private array $query_vars;

    /**
     * @param array<string,string> $query_vars
     */
    public function __construct(array $query_vars)
    {
        $this->query_vars = $query_vars;
    }

    public function isTruthy(Context $context): bool
    {
        foreach ($this->query_vars as $key => $value) {
            if ($context->queryVar($key) === $value) {
                return true;
            }
        }

        return false;
    }
}
