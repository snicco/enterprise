<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\fixtures;

use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;

final class HardCodedSecretGenerator implements TwoFactorSecretGenerator
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function generate(): string
    {
        return $this->secret;
    }
}
