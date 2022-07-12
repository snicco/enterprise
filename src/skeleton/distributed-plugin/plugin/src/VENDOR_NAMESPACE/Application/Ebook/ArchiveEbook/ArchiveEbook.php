<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Application\Ebook\ArchiveEbook;

/**
 * @psalm-immutable
 */
final class ArchiveEbook
{
    public string $ebook_id;

    public function __construct(string $ebook_id)
    {
        $this->ebook_id = $ebook_id;
    }
}
