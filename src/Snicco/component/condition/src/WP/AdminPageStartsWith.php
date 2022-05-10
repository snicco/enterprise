<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use function is_string;

final class AdminPageStartsWith implements Condition
{
    /**
     * @var string[]
     */
    private array $vendor_prefixes;

    /**
     * @param string|string[] $vendor_prefixes
     */
    public function __construct($vendor_prefixes)
    {
        $this->vendor_prefixes = Arr::toArray($vendor_prefixes);
    }

    public function isTruthy(Context $context): bool
    {
        if (! (new IsAdminArea())->isTruthy($context)) {
            return false;
        }

        $page_query_var = $context->queryVar('page');
        if (! is_string($page_query_var)) {
            return false;
        }

        if ('' === $page_query_var) {
            return false;
        }

        foreach ($this->vendor_prefixes as $vendor_prefix) {
            if (Str::startsWith($page_query_var, $vendor_prefix)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [self::class, [$this->vendor_prefixes]];
    }
}
