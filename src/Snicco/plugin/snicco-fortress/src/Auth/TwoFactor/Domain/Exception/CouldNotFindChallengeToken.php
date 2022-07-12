<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

use function sprintf;

final class CouldNotFindChallengeToken extends InvalidArgumentException
{
    public static function forSelector(string $token): self
    {
        return new self(sprintf('The 2FA-challenge selector [%s] is not valid. Possible brute-force attack.', $token));
    }
}
