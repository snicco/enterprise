<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\EventDispatcher;
use VENDOR_NAMESPACE\Application\DomainEvents;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Infrastructure\Persistence\AvailableEbooksUsingBetterWPDB;
use VENDOR_NAMESPACE\Infrastructure\Persistence\EbookRepositoryUsingBetterWPDB;
use VENDOR_NAMESPACE\Infrastructure\Snicco\DomainEventsUsingBetterWPHooks;

final class ProductionServiceContainer extends ServiceContainer
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $ebooks_table_name;

    private EventDispatcher $event_dispatcher;

    /**
     * @param non-empty-string $ebooks_table_name
     */
    public function __construct(EventDispatcher $event_dispatcher, BetterWPDB $db, string $ebooks_table_name)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->db = $db;
        $this->ebooks_table_name = $ebooks_table_name;
    }

    public function availableEbooks(): AvailableEbooks
    {
        return new AvailableEbooksUsingBetterWPDB($this->db, $this->ebooks_table_name);
    }

    protected function ebookRepository(): EbookRepository
    {
        return new EbookRepositoryUsingBetterWPDB($this->db, $this->ebooks_table_name);
    }

    protected function domainEvents(): DomainEvents
    {
        return new DomainEventsUsingBetterWPHooks($this->event_dispatcher);
    }
}
