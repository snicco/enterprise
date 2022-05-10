<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function rest_get_url_prefix;
use function trim;

final class IsWPRestAPIPrefix implements Condition
{
    private string $prefix;

    /**
     * @param string $prefix The vendor prefix without the WP-rest api prefix (wp-json)
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function isTruthy(Context $context): bool
    {
        $rest_prefix = rest_get_url_prefix();
        $full_prefix = '/' . trim($rest_prefix, '/') . '/' . trim($this->prefix, '/');

        return Str::startsWith($context->path(), $full_prefix);
    }

    public function toArray(): array
    {
        return [self::class, [$this->prefix]];
    }
}
