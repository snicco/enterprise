<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Domain\TwoFactorAuthenticator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateChallengeRedirectShutdownPHP;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateChallengeUser;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateRedirectContext;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\MappedEvent\WPAuthenticate;

use function array_replace;
use function filter_var;
use function is_string;
use function wp_authenticate;
use function wp_safe_redirect;

final class TwoFactorEventHandler implements EventSubscriber
{
    private TwoFactorSettings $two_factor_settings;

    private TwoFactorChallengeService $challenge_service;

    private EventDispatcher $event_dispatcher;

    private UrlGenerator $url_generator;

    public function __construct(
        TwoFactorSettings $two_factor_settings,
        TwoFactorChallengeService $challenge_service,
        EventDispatcher $event_dispatcher,
        UrlGenerator $url_generator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->event_dispatcher = $event_dispatcher;
        $this->url_generator = $url_generator;
        $this->challenge_service = $challenge_service;
    }

    public static function subscribedEvents(): array
    {
        return [
            WPAuthenticate::class => 'onWPAuthenticate',
        ];
    }

    /**
     * This method is run after a user was authenticated by any means using the.
     *
     * {@see wp_authenticate()} method.
     *
     * @todo Browser test
     */
    public function onWPAuthenticate(WPAuthenticate $event): void
    {
        $user = $event->user();

        if (! $this->two_factor_settings->isSetupCompleteForUser($user->ID)) {
            return;
        }

        $do_challenge = $this->event_dispatcher->dispatch(
            new WPAuthenticateChallengeUser($user)
        )->challenge_user;

        if (! $do_challenge) {
            return;
        }

        $challenge_id = $this->challenge_service->createChallenge($user->ID);

        $route_args = $this->addArgsFromEnvironment([
            TwoFactorAuthenticator::CHALLENGE_ID => $challenge_id,
        ]);

        $redirect_url = $this->url_generator->toRoute('fortress.2fa.challenge', $route_args);

        $event = $this->event_dispatcher->dispatch(
            new WPAuthenticateChallengeRedirectShutdownPHP(
                $user,
                $redirect_url
            )
        );

        if ($event->do_shutdown) {
            $this->redirectAndShutdown($redirect_url);
        }
    }

    /**
     * @param array<string,int|string> $args
     *
     * @return array<string,int|string>
     */
    private function addArgsFromEnvironment(array $args): array
    {
        $redirect_to = null;
        $remember_me = null;

        if (isset($_REQUEST['redirect_to']) && is_string($_REQUEST['redirect_to'])) {
            $redirect_to = $_REQUEST['redirect_to'];
        } elseif (isset($_SERVER['HTTP_REFERER']) && is_string($_SERVER['HTTP_REFERER'])) {
            $redirect_to = $_SERVER['HTTP_REFERER'];
        }

        if (isset($_REQUEST['remember_me']) || isset($_REQUEST['rememberme']) || isset($_REQUEST['remember'])) {
            $remember_me = true;
        }

        $context = $this->event_dispatcher->dispatch(
            new WPAuthenticateRedirectContext($redirect_to, $remember_me)
        );

        if(null !== $context->redirect_to) {
            $args['redirect_to'] = $context->redirect_to;
        }

        if($context->remember_me) {
            $args['remember_me'] = 1;
        }

        return $args;
    }

    /**
     * @return never-return
     *
     * @codeCoverageIgnore
     */
    private function redirectAndShutdown(string $redirect_url): void
    {
        wp_safe_redirect($redirect_url);

        exit(0);
    }
}
