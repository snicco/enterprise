<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain;

interface TwoFactorSecretGenerator
{
    public function generate(): string;
}
