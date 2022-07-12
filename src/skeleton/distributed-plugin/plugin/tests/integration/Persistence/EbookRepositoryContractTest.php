<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\integration\Persistence;

use Codeception\TestCase\WPTestCase;
use Generator;
use Snicco\Component\BetterWPDB\BetterWPDB;
use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookAlreadyArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Infrastructure\Persistence\EbookRepositoryUsingBetterWPDB;
use VENDOR_NAMESPACE\Tests\acceptance\Ebook\EbookRepositoryInMemory;

/**
 * @internal
 */
final class EbookRepositoryContractTest extends WPTestCase
{
    /**
     * @var non-empty-string
     */
    private string $table_name = 'wp_ebooks_testing';

    public function _setUp(): void
    {
        parent::_setUp();
        $db = BetterWPDB::fromWpdb();
        $db->unprepared(
            "create table if not exists `{$this->table_name}` (
            `id` char(36) not null,
            `description` varchar (10000) not null,
            `title` varchar (100) not null,
            `price` integer not null,
            `archived` boolean not null,
            primary key (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    public function _tearDown(): void
    {
        parent::_tearDown();
        $db = BetterWPDB::fromWpdb();
        $db->unprepared("DELETE FROM `{$this->table_name}`");
    }

    /**
     * @test
     * @dataProvider ebooks
     */
    public function that_it_can_save_and_load_ebook_entities(Ebook $ebook): void
    {
        foreach ($this->ebookRepositories() as $ebook_repository) {
            $this->assertFalse($ebook_repository->exists($ebook->id()));

            $ebook_repository->save($ebook);

            $this->assertTrue($ebook_repository->exists($ebook->id()));

            $this->assertEquals($ebook, $ebook_repository->getById($ebook->id()));
        }
    }

    /**
     * @test
     * @dataProvider ebooks
     */
    public function that_ebooks_can_be_modified_and_saved(Ebook $ebook): void
    {
        foreach ($this->ebookRepositories() as $ebook_repository) {
            $ebook_repository->save($ebook);

            $from_repo = $ebook_repository->getById($ebook->id());
            $from_repo->archive();

            $ebook_repository->save($from_repo);
            $from_repo2 = $ebook_repository->getById($from_repo->id());

            $this->expectException(EbookAlreadyArchived::class);
            $from_repo2->archive();
        }
    }

    /**
     * @test
     * @dataProvider ebooks
     */
    public function that_exceptions_are_thrown_for_missing_ebooks(Ebook $ebook): void
    {
        foreach ($this->ebookRepositories() as $ebook_repository) {
            $this->expectException(CouldNotFindEbook::class);
            $ebook_repository->getById($ebook->id());
        }
    }

    /**
     * @return Generator<array<Ebook>>
     */
    public function ebooks(): Generator
    {
        yield [
            Ebook::fromState([
                'id' => '0ffad2c2-93aa-4cea-aa79-923acc8ef801',
                'title' => 'Ebook title 1',
                'description' => 'Ebook description 1',
                'price' => 1000,
            ]),
        ];

        yield [
            Ebook::fromState([
                'id' => '0ffad2c2-93aa-4cea-aa79-923acc8ef802',
                'title' => 'Ebook title 2',
                'description' => 'Ebook description 2',
                'price' => 1000,
            ]),
        ];
    }

    /**
     * @return Generator<EbookRepository>
     */
    private function ebookRepositories(): Generator
    {
        yield new EbookRepositoryUsingBetterWPDB(BetterWPDB::fromWpdb(), $this->table_name);
        yield new EbookRepositoryInMemory();
    }
}
