<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure;

use PragmaRX\Google2FA\Google2FA;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSecretGenerator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\No2FaSettingsFound;

use function sprintf;

final class Google2FaProvider implements TwoFactorSecretGenerator, OTPValidator
{
    private TwoFactorSettings $two_factor_setup;
    
    private Google2FA $google_2fa;
    
    public function __construct(Google2FA $google_2fa, TwoFactorSettings $two_factor_setup)
    {
        $this->google_2fa = $google_2fa;
        $this->two_factor_setup = $two_factor_setup;
    }
    
    public function validate(string $otp_code, int $user_id) :void
    {
        try {
            $user_secret = $this->two_factor_setup->getSecretKey($user_id);
            $last_used = $this->two_factor_setup->lastUsedTimestamp($user_id);
        }catch (No2FaSettingsFound $e) {
            throw new InvalidOTPCode(
                sprintf('User with id [%s] did not complete 2FA setup yet.', $user_id)
            );
        }
        
        $last_check = null;
        if (null !== $last_used) {
            $last_check = (int)($last_used / $this->google_2fa->getKeyRegeneration());
        }
        
        $valid = $this->google_2fa->verifyKeyNewer(
            $user_secret,
            $otp_code,
            $last_check
        );
        
        if (false === $valid) {
            throw new InvalidOTPCode(sprintf('Invalid OTP for user [%s]', $user_id));
        }
        
        $this->two_factor_setup->updateLastUseTimestamp($user_id, time());
    }
    
    public function generate() :string
    {
        return $this->google_2fa->generateSecretKey();
    }
}
