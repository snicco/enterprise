<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\fixtures;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\No2FaSettingsFound;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\OTPValidator;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;

use function in_array;
use function md5;
use function sprintf;
use function time;

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
        if (in_array($otp_code, $this->used_codes, true)) {
            throw InvalidOTPCode::forUser($user_id);
        }

        try {
            $secret = $this->settings->getSecretKey($user_id);
        } catch (No2FaSettingsFound $e) {
            throw new InvalidOTPCode(sprintf('2FA not completed for user [%d]', $user_id));
        }

        if ($secret !== md5($otp_code)) {
            throw InvalidOTPCode::forUser($user_id);
        }

        $this->settings->updateLastUseTimestamp($user_id, time());
        $this->used_codes[] = $otp_code;
    }
}
