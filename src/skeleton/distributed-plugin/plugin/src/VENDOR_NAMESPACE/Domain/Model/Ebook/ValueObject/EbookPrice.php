<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject;

use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 */
final class EbookPrice
{
    private int $price;

    public function __construct(int $price)
    {
        Assert::greaterThan($price, 50, 'An ebook should cost at least 50 cents.');
        $this->price = $price;
    }

    public function asInt(): int
    {
        return $this->price;
    }
}
