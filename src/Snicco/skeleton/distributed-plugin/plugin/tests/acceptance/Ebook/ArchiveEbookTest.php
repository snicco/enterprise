<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\acceptance\Ebook;

use Codeception\Test\Unit;
use Ramsey\Uuid\Uuid;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbook;
use VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook\ArchiveEbookService;
use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookAlreadyArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookDescription;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookPrice;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookTitle;
use VENDOR_NAMESPACE\Infrastructure\Snicco\DomainEventsUsingBetterWPHooks;

/**
 * @internal
 */
final class ArchiveEbookTest extends Unit
{
    private EbookRepositoryInMemory $repository;

    private TestableEventDispatcher $testable_dispatcher;

    private ArchiveEbookService     $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EbookRepositoryInMemory();
        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->service = new ArchiveEbookService(
            $this->repository,
            new AvailableEbooksInMemory(),
            new DomainEventsUsingBetterWPHooks($this->testable_dispatcher)
        );
    }

    /**
     * @test
     */
    public function an_existing_ebook_can_be_archived(): void
    {
        $ebook_id = $this->givenAPersistedEbook();

        $command = new ArchiveEbook($ebook_id->asString());

        ($this->service)($command);

        $this->testable_dispatcher->assertDispatched(function (EbookWasArchived $event) use ($ebook_id) {
            return $event->id === $ebook_id;
        });
    }

    /**
     * @test
     */
    public function a_non_existing_ebook_can_not_be_archived(): void
    {
        $command = new ArchiveEbook($this->aValidUuid());

        $this->expectException(CouldNotFindEbook::class);

        ($this->service)($command);
    }

    /**
     * @test
     */
    public function an_archived_ebook_can_not_be_archived_again(): void
    {
        $ebook_id = $this->givenAPersistedEbook();

        $command = new ArchiveEbook($ebook_id->asString());

        ($this->service)($command);

        $this->expectException(EbookAlreadyArchived::class);

        ($this->service)($command);
    }

    private function givenAPersistedEbook(): EbookId
    {
        $this->repository->save(
            $ebook = Ebook::createNew(
                EbookId::fromString(Uuid::uuid4()->toString()),
                new EbookTitle('Ebook title'),
                new EbookDescription('Ebook description'),
                new EbookPrice(2500)
            ),
        );

        return $ebook->id();
    }

    private function aValidUuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}
