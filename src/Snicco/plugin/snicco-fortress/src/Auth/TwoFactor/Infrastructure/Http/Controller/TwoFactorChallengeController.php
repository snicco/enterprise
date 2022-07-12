<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\Http\Controller;

use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Enterprise\Fortress\Auth\Authenticator\Domain\TwoFactorAuthenticator;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\MalformedChallengeToken;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeExpired;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeWasTampered;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;

final class TwoFactorChallengeController extends Controller
{
    private TwoFactorChallengeService $challenge_service;

    private OTPValidator $validator;

    public function __construct(TwoFactorChallengeService $challenge_service, OTPValidator $validator)
    {
        $this->challenge_service = $challenge_service;
        $this->validator = $validator;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->isGet()) {
            return $this->displayChallenge($request);
        }

        return $this->processChallenge($request);
    }

    private function displayChallenge(Request $request): ViewResponse
    {
        $challenge_id = (string) $request->query(TwoFactorAuthenticator::CHALLENGE_ID);

        return $this->respondWith()
            ->view('snicco/fortress/2fa/challenge', [
                'redirect_to' => $redirect = (string) $request->query('redirect_to', \admin_url()),
                'challenge_id' => $challenge_id,
                'post_url' => $request->path(),
                'hidden_input_fields' => $this->inputFields($redirect, $challenge_id),
                'remember_me' => $request->boolean('remember_me'),
            ]);
    }

    private function processChallenge(Request $request): Response
    {
        if (! $request->accepts('application/json')) {
            throw new HttpException(406, \sprintf('Requests to %s must accept application/json', $request->path()));
        }

        $challenge_id = (string) $request->post('challenge_id');

        try {
            $user_id = $this->challenge_service->getChallengedUser($challenge_id);
        } catch (TwoFactorChallengeExpired $e) {
            return $this->respondWith()
                ->json([
                    'message' => \__('This two-factor challenge is expired. Please login again.', 'snicco-fortress'),
                ], 410);
        } catch (MalformedChallengeToken $e) {
            throw HttpException::fromPrevious(422, $e);
        } catch (TwoFactorChallengeWasTampered|CouldNotFindChallengeToken $e) {
            throw HttpException::fromPrevious(403, $e);
        }

        try {
            $this->validator->validate((string) $request->post('otp'), $user_id);

            $this->challenge_service->invalidate($challenge_id);
        } catch (InvalidOTPCode $e) {
            return $this->respondWith()
                ->json([
                    'message' => \__('Invalid one-time-password. Please try again.', 'snicco-fortress'),
                ], 401);
        }

        \wp_set_auth_cookie($user_id, $request->boolean('remember_me'));
        \wp_set_current_user($user_id);

        $redirect_url = (string) $request->post('redirect_to', \admin_url());

        return $this->respondWith()
            ->json([
                'redirect_url' => $redirect_url,
            ]);
    }

    private function inputFields(string $redirect, string $token): string
    {
        \ob_start(); ?>
		<input type='hidden' name='redirect_to' value="<?= $redirect; ?>">
		<input type='hidden' name='<?= \esc_attr(TwoFactorAuthenticator::CHALLENGE_ID); ?>' value="<?= $token; ?>">
		<?php
        return (string) \ob_get_clean();
    }
}
