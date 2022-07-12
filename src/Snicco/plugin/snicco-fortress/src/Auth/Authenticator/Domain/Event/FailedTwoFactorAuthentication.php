<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\Authenticator\Domain\Event;

use function sprintf;

final class FailedTwoFactorAuthentication extends FailedAuthenticationAttempt
{
    private string $user_id;

    public function __construct(?string $ip, string $user_id)
    {
        parent::__construct($ip);
        $this->user_id = $user_id;
    }

    public function message(): string
    {
        return sprintf('Failed two-factor authentication for user [%s]', $this->user_id);
    }
}
