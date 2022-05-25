<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Infrastructure;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\AuthBundle\Session\Domain\Event\SessionWasIdle;
use Snicco\Enterprise\AuthBundle\Session\Domain\Event\SessionWasRotated;
use Snicco\Enterprise\AuthBundle\Session\Domain\SessionManager;
use Snicco\Enterprise\AuthBundle\Session\Infrastructure\MappedEvent\SessionActivityRecorded;

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

    /**
     * @var array<string,true>
     */
    private array $updated = [];

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
        // The auth_cookie_valid hook will be dispatched multiple times
        // because WordPress does redundant things on auth_redirect() and determine_current_user()
        if (isset($this->updated[$event->raw_token])) {
            return;
        }

        $this->session_manager->updateActivity($event->raw_token);

        $this->updated[$event->raw_token] = true;
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
        // @todo Get remember value dynamically here.
        wp_set_auth_cookie($event->user_id, true, '', $event->new_token_plain);
    }
}
