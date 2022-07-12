<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\integration;

use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use VENDOR_NAMESPACE\Application\DomainEvents;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;
use VENDOR_NAMESPACE\Infrastructure\ServiceContainer;
use VENDOR_NAMESPACE\Infrastructure\Snicco\DomainEventsUsingBetterWPHooks;
use VENDOR_NAMESPACE\Tests\acceptance\Ebook\AvailableEbooksInMemory;
use VENDOR_NAMESPACE\Tests\acceptance\Ebook\EbookRepositoryInMemory;

final class IntegrationTestServiceContainer extends ServiceContainer
{
    private TestableEventDispatcher $event_dispatcher;

    private ?AvailableEbooksInMemory $available_ebooks = null;

    private EbookRepositoryInMemory  $ebook_repo;

    public function __construct(TestableEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->ebook_repo = new EbookRepositoryInMemory();
    }

    /**
     * @param Ebook[] $ebooks
     */
    public function availableEbooksAre(array $ebooks): void
    {
        $this->available_ebooks = new AvailableEbooksInMemory();
        foreach ($ebooks as $ebook) {
            $this->ebook_repo->save($ebook);
            $events = $ebook->releaseEvents();
            foreach ($events as $event) {
                if (! $event instanceof EbookWasCreated) {
                    continue;
                }
                $this->available_ebooks->add($event);
            }
        }
    }

    public function availableEbooks(): AvailableEbooksInMemory
    {
        if (! isset($this->available_ebooks)) {
            $this->available_ebooks = new AvailableEbooksInMemory();
        }

        return $this->available_ebooks;
    }

    protected function ebookRepository(): EbookRepository
    {
        return $this->ebook_repo;
    }

    protected function domainEvents(): DomainEvents
    {
        return new DomainEventsUsingBetterWPHooks($this->event_dispatcher);
    }
}
