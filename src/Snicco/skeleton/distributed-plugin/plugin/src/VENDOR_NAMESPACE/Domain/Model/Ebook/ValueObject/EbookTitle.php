<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject;

use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 */
final class EbookTitle
{
    private string $title;

    public function __construct(string $title)
    {
        Assert::stringNotEmpty($title, 'The ebook title should not be empty');
        Assert::lengthBetween($title, 10, 100, 'The ebook title should be between 10 and 100 characters.');
        $this->title = $title;
    }

    public function asString(): string
    {
        return $this->title;
    }
}
