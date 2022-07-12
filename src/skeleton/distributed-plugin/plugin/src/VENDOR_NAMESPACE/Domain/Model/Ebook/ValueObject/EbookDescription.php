<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject;

use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 */
final class EbookDescription
{
    private string $description;

    public function __construct(string $description)
    {
        Assert::stringNotEmpty($description, 'The ebook description should not be empty');
        Assert::lengthBetween(
            $description,
            10,
            10000,
            'The ebook description should be between 10 and 10000 characters.'
        );
        $this->description = $description;
    }

    public function asString(): string
    {
        return $this->description;
    }
}
