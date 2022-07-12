<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Tests\acceptance\Ebook;

use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\Ebook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\EbookRepository;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

final class EbookRepositoryInMemory implements EbookRepository
{
    /**
     * @var array<string,Ebook>
     */
    private array $ebooks = [];

    public function save(Ebook $ebook): void
    {
        $this->ebooks[$ebook->id()->asString()] = $ebook;
    }

    public function getById(EbookId $ebook_id): Ebook
    {
        if (! $this->exists($ebook_id)) {
            throw CouldNotFindEbook::withId($ebook_id);
        }

        return $this->ebooks[$ebook_id->asString()];
    }

    public function exists(EbookId $ebook_id): bool
    {
        return isset($this->ebooks[$ebook_id->asString()]);
    }
}
