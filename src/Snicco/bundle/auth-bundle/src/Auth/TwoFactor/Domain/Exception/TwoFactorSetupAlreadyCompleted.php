<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;
use function sprintf;

final class TwoFactorSetupAlreadyCompleted extends InvalidArgumentException
{
    public static function forUser(int $user_id): self
    {
        return new TwoFactorSetupAlreadyCompleted(sprintf(
            'Two factor settings were already created for user [%d]',
            $user_id
        ));
    }
}
