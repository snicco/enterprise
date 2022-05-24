<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;

interface OTPValidator
{
    
    /**
     * @throws InvalidOTPCode
     */
    public function validate(string $otp_code, int $user_id): void;
}
