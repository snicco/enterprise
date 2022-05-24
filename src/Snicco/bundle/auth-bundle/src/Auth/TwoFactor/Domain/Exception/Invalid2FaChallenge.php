<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception;

use InvalidArgumentException;

final class Invalid2FaChallenge extends InvalidArgumentException
{
    
    public static function forSelector(string $token) :self
    {
        return new self("The 2FA-challenge selector [$token] is not valid. Possible brute-force attack.");
    }
}