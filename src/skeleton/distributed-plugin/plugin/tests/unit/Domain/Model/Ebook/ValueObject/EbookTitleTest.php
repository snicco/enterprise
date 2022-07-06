<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\unit\Domain\Model\Ebook\ValueObject;

use Codeception\Test\Unit;
use InvalidArgumentException;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;

use function str_repeat;

/**
 * @internal
 */
final class EbookTitleTest extends Unit
{
    /**
     * @test
     */
    public function that_a_title_must_have_at_least_10_chars(): void
    {
        $title = new EbookTitle($string_title = str_repeat('x', 10));
        $this->assertSame($string_title, $title->asString());

        $this->expectException(InvalidArgumentException::class);
        new EbookTitle(str_repeat('x', 9));
    }

    /**
     * @test
     */
    public function that_a_title_can_not_have_more_than_100_chars(): void
    {
        $title = new EbookTitle($string_title = str_repeat('x', 100));
        $this->assertSame($string_title, $title->asString());

        $this->expectException(InvalidArgumentException::class);
        new EbookTitle(str_repeat('x', 101));
    }
}
