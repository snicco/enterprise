<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain;

use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;

interface OTPValidator
{
    /**
     * @throws InvalidOTPCode
     */
    public function validate(string $otp_code, int $user_id): void;
}
