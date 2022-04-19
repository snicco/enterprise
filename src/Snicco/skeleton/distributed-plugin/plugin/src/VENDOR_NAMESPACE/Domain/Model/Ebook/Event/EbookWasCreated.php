<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Domain\Model\Ebook\Event;

use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

/**
 * @psalm-immutable
 */
final class EbookWasCreated implements ExposeToWP
{
    public string $ebook_id;

    public string $title;

    public string $description;

    public int    $ebook_price;

    public function __construct(string $ebook_id, string $title, string $description, int $ebook_price)
    {
        $this->ebook_id = $ebook_id;
        $this->title = $title;
        $this->description = $description;
        $this->ebook_price = $ebook_price;
    }
}
