<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\unit\Domain\Model\Ebook;

use Codeception\Test\Unit;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookAlreadyArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookDescription;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookPrice;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;

/**
 * @internal
 */
final class EbookTest extends Unit
{
    /**
     * @test
     */
    public function that_a_newly_created_ebook_releases_events(): void
    {
        $ebook = $this->aNewEbook();

        $this->assertEquals([
            new EbookWasCreated(
                $ebook->id()
                    ->asString(),
                'VENDOR_TITLE ebook',
                'VENDOR TITLE ebook summary',
                1000
            ),
        ], $ebook->releaseEvents());

        $this->assertEquals([], $ebook->releaseEvents());
    }

    /**
     * @test
     */
    public function that_an_existing_ebook_has_no_events(): void
    {
        $ebook = $this->anExistingEbook();
        $this->assertSame([], $ebook->releaseEvents());
    }

    /**
     * @test
     */
    public function that_an_ebook_can_be_archived(): void
    {
        $ebook = $this->aNewEbook();

        $ebook->archive();

        $this->assertEquals([
            new EbookWasCreated(
                $ebook->id()
                    ->asString(),
                'VENDOR_TITLE ebook',
                'VENDOR TITLE ebook summary',
                1000
            ),
            new EbookWasArchived($ebook->id()),
        ], $ebook->releaseEvents());

        $state = $ebook->state();

        $this->assertTrue(isset($state['archived']));
        $this->assertTrue($state['archived']);
    }

    /**
     * @test
     */
    public function that_an_ebook_can_not_be_archived_twice(): void
    {
        $ebook = $this->anExistingEbook();

        $ebook->archive();

        $this->expectException(EbookAlreadyArchived::class);

        $ebook->archive();
    }

    private function aNewEbook(): Ebook
    {
        return Ebook::createNew(
            EbookId::fromString('0ffad2c2-93aa-4cea-aa79-923acc8ef802'),
            new EbookTitle('VENDOR_TITLE ebook'),
            new EbookDescription('VENDOR TITLE ebook summary'),
            new EbookPrice(1000),
        );
    }

    private function anExistingEbook(): Ebook
    {
        $state = [
            'id' => '0ffad2c2-93aa-4cea-aa79-923acc8ef802',
            'title' => 'VENDOR_TITLE ebook',
            'description' => 'VENDOR TITLE ebook summary',
            'price' => 1000,
        ];

        return Ebook::fromState($state);
    }
}
