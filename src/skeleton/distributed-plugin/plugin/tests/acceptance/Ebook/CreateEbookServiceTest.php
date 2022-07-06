<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\acceptance\Ebook;

use Codeception\Test\Unit;
use Ramsey\Uuid\Uuid;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbook;
use VENDOR_NAMESPACE\Application\Ebook\CreateEbook\CreateEbookService;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;
use VENDOR_NAMESPACE\Infrastructure\Snicco\DomainEventsUsingBetterWPHooks;

/**
 * @internal
 */
final class CreateEbookServiceTest extends Unit
{
    private EbookRepositoryInMemory $repository;

    private CreateEbookService $service;

    private AvailableEbooksInMemory $available_ebooks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EbookRepositoryInMemory();
        $this->service = new CreateEbookService(
            $this->repository,
            new DomainEventsUsingBetterWPHooks(new TestableEventDispatcher($dispatcher = new BaseEventDispatcher()))
        );
        $this->available_ebooks = new AvailableEbooksInMemory();
        $dispatcher->listen(
            EbookWasCreated::class,
            fn (EbookWasCreated $event) => $this->available_ebooks->add($event)
        );
    }

    /**
     * @test
     */
    public function a_created_ebook_shows_up_in_the_list_of_available_ebooks(): void
    {
        $command = new CreateEbook($id = $this->aValidUuid(), 'Ebook title', 'Ebook description', 2500);

        ($this->service)($command);

        $ebook_for_customer = $this->available_ebooks->getEbookForCustomer(
            EbookId::fromString($command->ebook_id)
        );

        $this->assertSame('Ebook title', $ebook_for_customer->title());
        $this->assertSame('Ebook description', $ebook_for_customer->description());
        $this->assertSame('$25.00', $ebook_for_customer->formattedPrice());
        $this->assertSame($id, $ebook_for_customer->id());
    }

    private function aValidUuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}
