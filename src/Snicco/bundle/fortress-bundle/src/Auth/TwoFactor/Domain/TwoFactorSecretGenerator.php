<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain;

interface TwoFactorSecretGenerator
{
    public function generate(): string;
}
