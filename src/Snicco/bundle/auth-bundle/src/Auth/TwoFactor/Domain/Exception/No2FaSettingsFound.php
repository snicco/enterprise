<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;
use function sprintf;

final class No2FaSettingsFound extends InvalidArgumentException
{
    public static function forUser(int $user_id): self
    {
        return new self(sprintf('No 2FA-settings were found for user with id [%d]', $user_id));
    }
}
