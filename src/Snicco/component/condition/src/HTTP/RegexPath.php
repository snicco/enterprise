<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\HTTP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;
use function ltrim;
use function preg_match;
use function sprintf;

final class RegexPath implements Condition
{
    private string $pattern;

    /**
     * @param string $pattern The request pattern without any delimiters
     */
    public function __construct(string $pattern)
    {
        if (Str::startsWith($pattern, '\\/')) {
            $pattern = Str::afterFirst($pattern, '\\/');
        }

        $this->pattern = $pattern;
    }

    public function isTruthy(Context $context): bool
    {
        $path = ltrim($context->path(), '/');

        return 1 === preg_match(sprintf('#%s#', $this->pattern), $path);
    }

    public function toArray(): array
    {
        return [self::class, [$this->pattern]];
    }
}
