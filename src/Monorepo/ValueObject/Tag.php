<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Monorepo\ValueObject;

use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function preg_match;
use function sprintf;

final class Tag
{
    private string $tag_numerical;

    private string $tag;

    /**
     * @param non-empty-string $tag
     */
    public function __construct(string $tag)
    {
        Assert::regex($tag, '/^v?\d+\.\d+.\d+$/', sprintf('Invalid tag %s', $tag));

        $this->tag_numerical = Str::afterFirst($tag, 'v');
        $this->tag = $tag;
    }

    public function isMajor(): bool
    {
        return 1 === preg_match('#^[1-9]\d*.0.0$#', $this->tag_numerical);
    }

    public function isMinor(): bool
    {
        return 1 === preg_match('#^[1-9]\d*.[1-9]\d*.0$#', $this->tag_numerical);
    }

    public function isPatch(): bool
    {
        return 1 === preg_match('#^[1-9]\d*.\d+.[1-9]\d*$#', $this->tag_numerical);
    }

    public function asString(): string
    {
        return $this->tag;
    }
}
