<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\Authenticator\Domain;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Auth\Auth\Authenticator\Domain\LoginResult;
use Snicco\Enterprise\Bundle\Auth\Auth\Authenticator\Domain\Authenticator;
use Snicco\Enterprise\Bundle\Auth\Auth\Event\FailedTwoFactorAuthentication;
use Snicco\Enterprise\Bundle\Auth\Auth\RequestAttributes;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\InvalidBackupCode;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Auth\Auth\User\UserProvider;
use WP_User;

use function is_bool;

final class TwoFactorAuthenticator extends Authenticator
{
    private OTPValidator $two_factor_validator;

    private EventDispatcher $event_dispatcher;

    private UserProvider $user_provider;

    private TwoFactorSettings $two_factor_settings;

    public function __construct(
        EventDispatcher $event_dispatcher,
        TwoFactorSettings $two_factor_settings,
        OTPValidator $two_factor_provider,
        UserProvider $user_provider
    ) {
        $this->two_factor_validator = $two_factor_provider;
        $this->event_dispatcher = $event_dispatcher;
        $this->user_provider = $user_provider;
        $this->two_factor_settings = $two_factor_settings;
    }

    public function attempt(Request $request, callable $next): LoginResult
    {
        /** @var mixed|string|null $challenged_user */
        $challenged_user = $request->getAttribute(RequestAttributes::CHALLENGED_USER);

        if (null === $challenged_user) {
            return $next($request);
        }

        $user = $this->user_provider->getUserByIdentifier((string) $challenged_user);

        /** @var mixed $remember */
        $remember = $request->getAttribute(RequestAttributes::REMEMBER_CHALLENGED_USER);

        if (null !== $remember && ! is_bool($remember)) {
            $remember = null;
        }

        if ($request->post('backup_code')) {
            return $this->authenticateWithBackupCode($user, $request, $remember);
        }

        return $this->authenticateWithOTP($request, $user, $remember);
    }

    private function authenticateWithBackupCode(WP_User $user, Request $request, ?bool $remember): LoginResult
    {
        $user_backup_codes = $this->two_factor_settings->getBackupCodes($user->ID);
        $provided_code = (string) $request->post('backup_code');

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
            $this->two_factor_validator->validate($request, $user->ID);
        } catch (InvalidOTPCode $e) {
            $this->event_dispatcher->dispatch(
                new FailedTwoFactorAuthentication((string) $request->ip(), (string) $user->ID)
            );

            return LoginResult::failed();
        }

        return new LoginResult($user, $remember);
    }
}
