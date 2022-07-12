<?php

declare(strict_types=1);

namespace VENDOR_NAMESPACE\Infrastructure\WordPress;

use Snicco\Component\BetterWPDB\BetterWPDB;

final class ActivatePlugin
{
    private BetterWPDB $db;

    private string     $table_name;

    public function __construct(BetterWPDB $db, string $table_name)
    {
        $this->db = $db;
        $this->table_name = $table_name;
    }

    public function __invoke(): void
    {
        $this->db->unprepared(
            "create table if not exists `{$this->table_name}` (
            `id` char(36) not null,
            `description` varchar (10000) not null,
            `title` varchar (100) not null,
            `price` integer not null,
            `archived` boolean not null,
            primary key (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }
}
