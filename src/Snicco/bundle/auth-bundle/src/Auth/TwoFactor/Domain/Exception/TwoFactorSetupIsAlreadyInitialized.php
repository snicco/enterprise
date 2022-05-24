<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

use function sprintf;

final class TwoFactorSetupIsAlreadyInitialized extends InvalidArgumentException
{
    public static function forUser(int $user_id): self
    {
        return new self(sprintf('Cant initiate 2FA setup twice. The user [%d] has a pending 2FA setup.', $user_id));
    }
}