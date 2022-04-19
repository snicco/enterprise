<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\ListAvailableEbooks;

use VENDOR_NAMESPACE\Domain\Model\Ebook\CouldNotFindEbook;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

interface AvailableEbooks
{
    /**
     * @return EbookForCustomer[]
     */
    public function forCustomers(): array;

    /**
     * @throws CouldNotFindEbook
     */
    public function getEbookForCustomer(EbookId $id): EbookForCustomer;
}
