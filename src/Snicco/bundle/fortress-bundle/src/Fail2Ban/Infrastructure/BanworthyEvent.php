<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Fail2Ban\Infrastructure;

interface BanworthyEvent
{
    public function priority(): int;

    public function message(): string;

    /**
     * @return non-empty-string|null
     */
    public function ip(): ?string;
}
