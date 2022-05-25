<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;
use function sprintf;

final class TwoFactorChallengeWasTampered extends InvalidArgumentException
{
    public static function forSelector(string $selector): self
    {
        return new self(
            sprintf('The Two-Factor challenge with selector [%s] has been tampered. It has been deleted.', $selector)
        );
    }
}
