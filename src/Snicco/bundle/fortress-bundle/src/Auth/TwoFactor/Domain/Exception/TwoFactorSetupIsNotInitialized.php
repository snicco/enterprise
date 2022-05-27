<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

use function sprintf;

final class TwoFactorSetupIsNotInitialized extends InvalidArgumentException
{
    public static function forUser(int $user_id): self
    {
        return new self(sprintf('Cant complete 2FA setup. No 2FA settings have been created for user [%d]', $user_id));
    }
}
