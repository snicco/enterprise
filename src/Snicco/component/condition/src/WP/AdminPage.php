<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\WP;

use Snicco\Component\StrArr\Arr;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;
use function in_array;

final class AdminPage implements Condition
{
    /**
     * @var string[]
     */
    private array $pages;

    /**
     * @param string[]|string $pages
     */
    public function __construct($pages)
    {
        $this->pages = Arr::toArray($pages);
    }

    public function isTruthy(Context $context): bool
    {
        if (! (new IsAdminArea())->isTruthy($context)) {
            return false;
        }

        $page_query_var = $context->queryVar('page');

        return in_array($page_query_var, $this->pages, true);
    }

    public function toArray(): array
    {
        return [self::class, [$this->pages]];
    }
}
