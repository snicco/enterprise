<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain;

interface TwoFactorSecretGenerator
{
    public function generate(): string;
}
