<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress;

use Snicco\Component\BetterWPDB\BetterWPDB;

final class UninstallPlugin
{
    private BetterWPDB $db;

    private string     $ebook_table;

    private bool       $data_deletion_confirmed;

    public function __construct(BetterWPDB $db, string $ebook_table, bool $data_deletion_confirmed)
    {
        $this->db = $db;
        $this->ebook_table = $ebook_table;
        $this->data_deletion_confirmed = $data_deletion_confirmed;
    }

    public function __invoke(): void
    {
        if ($this->data_deletion_confirmed) {
            return;
        }

        $this->db->unprepared("DROP TABLE IF EXISTS `{$this->ebook_table}`");
    }
}
