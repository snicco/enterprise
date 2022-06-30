<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain;

interface TwoFactorSecretGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}
