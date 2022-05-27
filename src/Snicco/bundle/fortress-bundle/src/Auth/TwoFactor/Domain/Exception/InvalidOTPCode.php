<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

use function sprintf;

final class InvalidOTPCode extends InvalidArgumentException
{
    public static function forUser(int $user_id): self
    {
        return new self(sprintf('Invalid OTP code provided for user [%d]', $user_id));
    }
}
