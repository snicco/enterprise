<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook;

use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

interface EbookRepository
{
    public function save(Ebook $ebook): void;

    /**
     * @throws CouldNotFindEbook
     */
    public function getById(EbookId $ebook_id): Ebook;

    public function exists(EbookId $ebook_id): bool;
}
