<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\TwoFactor;

use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use WP_User;
use function sprintf;
use function time;

final class Google2FaValidator implements TwoFactorCredentialsValidator
{
    private Google2FA            $google_2fa;

    private TwoFactorOTPSettings $two_factor_setup;

    public function __construct(Google2FA $google_2fa, TwoFactorOTPSettings $two_factor_setup)
    {
        $this->google_2fa = $google_2fa;
        $this->two_factor_setup = $two_factor_setup;
    }

    public function validate(Request $request, WP_User $user): void
    {
        $id = $user->ID;
        if (! $this->two_factor_setup->isSetupCompleteForUser($id)) {
            throw new InvalidTwoFactorCredentials(sprintf('User with id [%s] did not complete 2FA setup yet.', $id));
        }

        $otp = (string) $request->post('otp');
        $user_secret = $this->two_factor_setup->getSecretKey($id);
        $last_used = $this->two_factor_setup->lastUsedTimestamp($id);

        $last_check = null;
        if (null !== $last_used) {
            $last_check = (int) ($last_used / $this->google_2fa->getKeyRegeneration());
        }

        $valid = $this->google_2fa->verifyKeyNewer($user_secret, $otp, $last_check);

        if (false === $valid) {
            throw new InvalidTwoFactorCredentials(sprintf('Invalid OTP for user [%s]', $id));
        }

        $this->two_factor_setup->updateLastUseTimestamp($id, time());
    }
}
