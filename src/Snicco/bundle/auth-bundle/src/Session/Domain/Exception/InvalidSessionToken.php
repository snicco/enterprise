<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Domain\Exception;

use InvalidArgumentException;

final class InvalidSessionToken extends InvalidArgumentException
{
    
    public static function forToken(string $token) :self
    {
        return new self("The session token [$token] does not exist in the session repository");
    }
}
