<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Common;

use Webmozart\Assert\Assert;

/**
 * @psalm-immutable
 */
trait Uuid
{
    private string $id;

    private function __construct(string $id)
    {
        Assert::uuid($id);
        $this->id = $id;
    }

    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public function asString(): string
    {
        return $this->id;
    }
}
