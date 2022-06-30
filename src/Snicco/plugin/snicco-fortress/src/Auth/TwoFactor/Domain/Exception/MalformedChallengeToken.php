<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

final class MalformedChallengeToken extends InvalidArgumentException
{
    public static function becauseOfIncorrectLength(): self
    {
        return new self('The token length does not match the expected length.');
    }
}
