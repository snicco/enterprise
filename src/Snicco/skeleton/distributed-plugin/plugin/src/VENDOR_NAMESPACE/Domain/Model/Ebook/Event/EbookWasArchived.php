<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;
use VENDOR_NAMESPACE\Domain\Model\Ebook\ValueObject\EbookId;

/**
 * @psalm-immutable
 */
final class EbookWasArchived implements ExposeToWP
{
    public EbookId $id;

    public function __construct(EbookId $id)
    {
        $this->id = $id;
    }
}
