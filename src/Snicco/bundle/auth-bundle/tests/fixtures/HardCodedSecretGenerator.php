<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\fixtures;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;

final class HardCodedSecretGenerator implements TwoFactorSecretGenerator
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function create(): string
    {
        return $this->secret;
    }
}
