<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Domain;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use WP_User;

final class RedirectTwoFactorAuthenticator extends Authenticator
{
    private string $route_name;

    private ResponseFactory $response_factory;

    private UrlGenerator $url_generator;

    private TwoFactorSettings $two_2fa_settings;

    private TwoFactorChallengeService $challenge_service;

    public function __construct(
        TwoFactorSettings $two_2fa_settings,
        TwoFactorChallengeService $challenge_service,
        ResponseFactory $response_factory,
        UrlGenerator $url_generator,
        string $route_name_2fa
    ) {
        $this->route_name = $route_name_2fa;
        $this->response_factory = $response_factory;
        $this->url_generator = $url_generator;
        $this->two_2fa_settings = $two_2fa_settings;
        $this->challenge_service = $challenge_service;
    }

    public function attempt(Request $request, callable $next): LoginResult
    {
        $result = $next($request);

        if (! $result->isAuthenticated()) {
            return $result;
        }

        $user = $result->authenticatedUser();

        if ($this->two_2fa_settings->isSetupCompleteForUser($user->ID)) {
            return $this->redirectToChallenge($user, $result->rememberUser());
        }

        return $result;
    }

    private function redirectToChallenge(WP_User $user, ?bool $remember_user): LoginResult
    {
        $token = $this->challenge_service->createChallenge($user->ID);

        $challenge_url = $this->url_generator->toRoute(
            $this->route_name,
            [
                TwoFactorAuthenticator::CHALLENGE_ID => $token,
                'remember_me' => (int) $remember_user,
            ]
        );

        $response = $this->response_factory->redirect($challenge_url);

        return new LoginResult(null, null, $response);
    }
}
