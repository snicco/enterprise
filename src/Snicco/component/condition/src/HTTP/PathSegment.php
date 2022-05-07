<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

final class PathSegment implements Condition
{
    /**
     * @var non-empty-string
     */
    private string $path_segment;

    /**
     * @param non-empty-string $path_segment
     */
    public function __construct(string $path_segment)
    {
        $this->path_segment = $path_segment;
    }

    public function isTruthy(Context $context): bool
    {
        return Str::contains($context->path(), $this->path_segment);
    }
}
