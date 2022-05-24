<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\LoginResult;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use WP_User;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\Authenticator;

final class RedirectTwoFactorAuthenticator extends Authenticator
{
    /**
     * @var string
     */
    public const CHALLENGED_USER_ID_HEADER = '_challenged_user';

    private string $route_name_2fa;

    private ResponseFactory $response_factory;

    private UrlGenerator $url_generator;

    private TwoFactorSettings $two_2fa_settings;

    public function __construct(
        TwoFactorSettings $two_2fa_settings,
        ResponseFactory $response_factory,
        UrlGenerator $url_generator,
        string $route_name_2fa
    ) {
        $this->route_name_2fa = $route_name_2fa;
        $this->response_factory = $response_factory;
        $this->url_generator = $url_generator;
        $this->two_2fa_settings = $two_2fa_settings;
    }

    public function attempt(Request $request, callable $next): LoginResult
    {
        $result = $next($request);

        if (! $result->isSuccess()) {
            return $result;
        }

        $user = $result->authenticatedUser();

        if ($this->two_2fa_settings->isSetupCompleteForUser($user->ID)) {
            return $this->redirectTo2FaForm($user);
        }

        return $result;
    }

    private function redirectTo2FaForm(WP_User $user): LoginResult
    {
        $url = $this->url_generator->toRoute($this->route_name_2fa);

        $response = $this->response_factory->redirect($url)
            ->withHeader(self::CHALLENGED_USER_ID_HEADER, (string) $user->ID);

        return new LoginResult(null, null, $response);
    }
}
