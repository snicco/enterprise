<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session\Infrastructure;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\Infrastructure\MappedEvent\SessionActivityRecorded;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\Event\SessionWasRotated;

use function setcookie;
use function wp_set_auth_cookie;

use const AUTH_COOKIE;
use const COOKIE_DOMAIN;
use const LOGGED_IN_COOKIE;
use const PLUGINS_COOKIE_PATH;
use const SECURE_AUTH_COOKIE;

final class SessionEventHandler implements EventSubscriber
{
    private SessionManager $session_manager;
    
    public function __construct(SessionManager $session_manager)
    {
        $this->session_manager = $session_manager;
    }

    public static function subscribedEvents(): array
    {
        return [
            SessionActivityRecorded::class => 'onSessionActivityRecorded',
            SessionWasIdle::class => 'onSessionIdle',
            SessionWasRotated::class => 'onSessionRotated',
        ];
    }

    public function onSessionActivityRecorded(SessionActivityRecorded $event): void
    {
        $this->session_manager->updateActivity($event->raw_token);
    }

    /**
     * If a session is idle due to lack of activity we need to explicitly clear
     * the auth cookies, in order to not keep sending the same idle cookie in
     * the frontend.
     *
     * In the admin area users are automatically logged auth by auth_redirect()
     *
     * @codeCoverageIgnore
     *
     * @todo This can only to be tested in a browser test.
     */
    public function onSessionIdle(SessionWasIdle $event): void
    {
        /**
         * @var string $cookie_domain
         *
         * @psalm-suppress UndefinedConstant
         */
        $cookie_domain = COOKIE_DOMAIN ?: '';

        setcookie(AUTH_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => ADMIN_COOKIE_PATH,
            'domain' => $cookie_domain,
        ]);

        setcookie(SECURE_AUTH_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => ADMIN_COOKIE_PATH,
            'domain' => $cookie_domain,
        ]);

        setcookie(AUTH_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => PLUGINS_COOKIE_PATH,
            'domain' => $cookie_domain,
        ]);

        setcookie(SECURE_AUTH_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => PLUGINS_COOKIE_PATH,
            'domain' => $cookie_domain,
        ]);

        setcookie(LOGGED_IN_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => COOKIEPATH,
            'domain' => $cookie_domain,
        ]);

        setcookie(LOGGED_IN_COOKIE, 'deleted', [
            'expires' => 1,
            'path' => SITECOOKIEPATH,
            'domain' => $cookie_domain,
        ]);

        // Settings cookies.
        setcookie('wp-settings-' . (string) $event->user_id, 'deleted', [
            'expires' => 1,
            'path' => SITECOOKIEPATH,
        ]);

        setcookie('wp-settings-time-' . (string) $event->user_id, 'deleted', [
            'expires' => 1,
            'path' => SITECOOKIEPATH,
        ]);

        // Post password cookie.
        setcookie('wp-postpass_' . COOKIEHASH, 'deleted', [
            'expires' => 1,
            'path' => COOKIEPATH,
            'domain' => $cookie_domain,
        ]);
    }

    /**
     * We rotated a still valid session id. We need to set a new cookie in the
     * browser otherwise the user will be logged in during the next request.
     *
     * @codeCoverageIgnore
     *
     * @todo This can only to be tested in a browser test.
     */
    public function onSessionRotated(SessionWasRotated $event): void
    {
        // Remember_me can be set to true because we are filtering the expiration of the cookie anyway.
        wp_set_auth_cookie($event->user_id, true, '', $event->new_token_plain);
    }
}
