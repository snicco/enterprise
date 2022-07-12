<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Session\Domain\Exception;

use InvalidArgumentException;

use function sprintf;

final class InvalidSessionToken extends InvalidArgumentException
{
    public static function forToken(string $token): self
    {
        return new self(sprintf('The session token [%s] does not exist in the session repository', $token));
    }
}
