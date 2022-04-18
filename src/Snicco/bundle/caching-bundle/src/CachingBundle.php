<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Caching;

final class CachingBundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/caching-bundle';

    public static function testMethod(): string
    {
        return 'foo';
    }
}
