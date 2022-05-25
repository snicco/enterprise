<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\Event\FailedTwoFactorAuthentication;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\InvalidBackupCode;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Auth\User\Domain\UserProvider;
use WP_User;

use function is_bool;

final class TwoFactorAuthenticator extends Authenticator
{
    /**
     * @var string
     */
    public const CHALLENGE_ID = 'challenge_id';

    /**
     * @var string
     */
    public const BACKUP_CODE_KEY = 'backup_code';

    /**
     * @var string
     */
    public const OTP_KEY = 'otp';

    private OTPValidator $two_factor_validator;

    private EventDispatcher $event_dispatcher;

    private UserProvider $user_provider;

    private TwoFactorSettings $two_factor_settings;

    private TwoFactorChallengeService $challenge_service;

    public function __construct(
        EventDispatcher $event_dispatcher,
        TwoFactorSettings $two_factor_settings,
        TwoFactorChallengeService $challenge_service,
        OTPValidator $two_factor_provider,
        UserProvider $user_provider
    ) {
        $this->two_factor_validator = $two_factor_provider;
        $this->event_dispatcher = $event_dispatcher;
        $this->user_provider = $user_provider;
        $this->two_factor_settings = $two_factor_settings;
        $this->challenge_service = $challenge_service;
    }

    public function attempt(Request $request, callable $next): LoginResult
    {
        $token = (string) $request->post(self::CHALLENGE_ID);

        if (empty($token)) {
            return $next($request);
        }

        $challenged_user = $this->challenge_service->getChallengedUser($token);

        $user = $this->user_provider->getUserByIdentifier((string) $challenged_user);

        /** @var mixed $remember */
        $remember = $request->post('remember_me');

        if (null !== $remember && ! is_bool($remember)) {
            $remember = null;
        }

        $backup_code = (string) $request->post(self::BACKUP_CODE_KEY);

        if (! empty($backup_code)) {
            return $this->authenticateWithBackupCode($user, $backup_code, $remember);
        }

        return $this->authenticateWithOTP($request, $user, $remember);
    }

    private function authenticateWithBackupCode(WP_User $user, string $provided_code, ?bool $remember): LoginResult
    {
        $request = null;
        $user_backup_codes = $this->two_factor_settings->getBackupCodes($user->ID);

        try {
            $user_backup_codes->revoke($provided_code);
        } catch (InvalidBackupCode $e) {
            $this->event_dispatcher->dispatch(
                new FailedTwoFactorAuthentication((string) $request->ip(), (string) $user->ID)
            );

            return LoginResult::failed();
        }

        $this->two_factor_settings->updateBackupCodes($user->ID, $user_backup_codes);

        return new LoginResult($user, $remember);
    }

    private function authenticateWithOTP(Request $request, WP_User $user, ?bool $remember): LoginResult
    {
        try {
            $this->two_factor_validator->validate((string) $request->post('otp'), $user->ID);
        } catch (InvalidOTPCode $e) {
            $this->event_dispatcher->dispatch(
                new FailedTwoFactorAuthentication((string) $request->ip(), (string) $user->ID)
            );

            return LoginResult::failed();
        }

        return new LoginResult($user, $remember);
    }
}
