<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Domain\Event;

use function sprintf;

final class FailedPasswordAuthentication extends FailedAuthenticationAttempt
{
    private string $provided_user_login;

    public function __construct(string $ip, string $provided_user_login)
    {
        parent::__construct($ip);
        $this->provided_user_login = $provided_user_login;
    }

    public function message(): string
    {
        return sprintf('Failed password authentication attempt for user [%s]', $this->provided_user_login);
    }
}
