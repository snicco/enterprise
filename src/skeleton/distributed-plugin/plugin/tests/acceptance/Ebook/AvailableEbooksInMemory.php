<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\acceptance\Ebook;

use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\AvailableEbooks;
use VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks\EbookForCustomer;
use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasArchived;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Event\EbookWasCreated;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

use function array_values;

final class AvailableEbooksInMemory implements AvailableEbooks
{
    /**
     * @var array<string,EbookForCustomer>
     */
    private array $ebooks = [];

    public function add(EbookWasCreated $event): void
    {
        $this->ebooks[$event->ebook_id] = new EbookForCustomer(
            $event->ebook_id,
            $event->title,
            $event->description,
            $event->ebook_price
        );
    }

    public function remove(EbookWasArchived $event): void
    {
        unset($this->ebooks[$event->id->asString()]);
    }

    public function forCustomers(): array
    {
        return array_values($this->ebooks);
    }

    public function getEbookForCustomer(EbookId $id): EbookForCustomer
    {
        if (! isset($this->ebooks[$id->asString()])) {
            throw CouldNotFindEbook::withId($id);
        }

        return $this->ebooks[$id->asString()];
    }
}
