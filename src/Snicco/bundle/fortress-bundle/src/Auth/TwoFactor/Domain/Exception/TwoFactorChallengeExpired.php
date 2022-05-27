<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception;

use RuntimeException;

use function sprintf;

final class TwoFactorChallengeExpired extends RuntimeException
{
    public static function forSelector(string $selector, int $expired_since): self
    {
        return new self(
            sprintf(
                'The two-factor challenge with selector [%s] was expired since [%d] second/s.',
                $selector,
                $expired_since
            )
        );
    }
}
