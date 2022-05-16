<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Fail2Ban;

interface BannableEvent
{
    public function priority(): int;

    public function message(): string;

    public function ip(): string;
}
