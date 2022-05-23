<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\fixtures;

use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\OTPValidator;

use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\Exception\InvalidOTPCode;

use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\Exception\No2FaSettingsFound;

use function md5;
use function time;
use function in_array;

final class MD5OTPValidator implements OTPValidator
{
    
    private TwoFactorSettings $settings;
    
    /**
     * @var string[]
     */
    private array $used_codes = [];
    
    public function __construct(TwoFactorSettings $settings)
    {
        $this->settings = $settings;
    }

    public function validate(string $otp_code, int $user_id): void
    {
        if(in_array($otp_code, $this->used_codes, true)) {
            throw InvalidOTPCode::forUser($user_id);
        }
        
        try {
            $secret = $this->settings->getSecretKey($user_id);
        }catch (No2FaSettingsFound $e) {
            throw new InvalidOTPCode("2FA not completed for user [$user_id]");
        }
    
        if($secret !== md5($otp_code)) {
            throw InvalidOTPCode::forUser($user_id);
        }
        
        $this->settings->updateLastUseTimestamp($user_id, time());
        $this->used_codes[] = $otp_code;
    }

}
