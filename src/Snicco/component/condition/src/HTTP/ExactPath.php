<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;
use function ltrim;

final class ExactPath implements Condition
{
    /**
     * @param non-empty-string $path
     */
    private string $path;

    /**
     * @param non-empty-string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isTruthy(Context $context): bool
    {
        return ltrim($this->path, '/') === ltrim($context->path(), '/');
    }
}
