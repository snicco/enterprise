<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Auth\Event\SessionActivityRecorded;

use function wp_set_auth_cookie;

use const AUTH_COOKIE;
use const COOKIE_DOMAIN;
use const LOGGED_IN_COOKIE;
use const SECURE_AUTH_COOKIE;
use const PLUGINS_COOKIE_PATH;

final class SessionEventHandler implements EventSubscriber
{
    private SessionRepository $session_repository;
    
    public function __construct(SessionRepository $session_repository)
    {
        $this->session_repository = $session_repository;
    }
    
    public static function subscribedEvents() :array
    {
        return [
            SessionActivityRecorded::class => 'onSessionActivityRecorded',
            SessionWasIdle::class => 'onSessionIdle',
            SessionWasRotated::class => 'onSessionRotated'
        ];
    }
    
    public function onSessionActivityRecorded(SessionActivityRecorded $event) :void
    {
        $this->session_repository->touch(
            $this->session_repository->hashToken($event->raw_token)
        );
    }
    
    /**
     * If a session is idle due to lack of activity we need to explicitly clear the auth cookies,
     * in order to not keep sending the same idle cookie in the frontend.
     *
     * In the admin area users are automatically logged auth by auth_redirect()
     *
     * @codeCoverageIgnore
     * @todo This can only to be tested in a browser test.
     */
    public function onSessionIdle(SessionWasIdle $event) :void
    {
        /**
         * @var string $cookie_domain
         *
         * @psalm-suppress UndefinedConstant
         */
        $cookie_domain = COOKIE_DOMAIN ?: '';
        
        setcookie( AUTH_COOKIE, 'deleted', 1, ADMIN_COOKIE_PATH, $cookie_domain );
        setcookie( SECURE_AUTH_COOKIE, 'deleted', 1, ADMIN_COOKIE_PATH, $cookie_domain );
        setcookie( AUTH_COOKIE, 'deleted', 1, PLUGINS_COOKIE_PATH, $cookie_domain );
        setcookie( SECURE_AUTH_COOKIE, 'deleted', 1, PLUGINS_COOKIE_PATH, $cookie_domain );
        setcookie( LOGGED_IN_COOKIE, 'deleted', 1, COOKIEPATH, $cookie_domain );
        setcookie( LOGGED_IN_COOKIE, 'deleted', 1, SITECOOKIEPATH, $cookie_domain );
    
        // Settings cookies.
        setcookie( 'wp-settings-' . (string) $event->user_id, 'deleted', 1, SITECOOKIEPATH );
        setcookie( 'wp-settings-time-' . (string) $event->user_id, 'deleted', 1, SITECOOKIEPATH );
    
        // Post password cookie.
        setcookie( 'wp-postpass_' . COOKIEHASH, 'deleted', 1, COOKIEPATH, $cookie_domain );
    }
    
    /**
     * We rotated a still valid session id. We need to set a new cookie in the browser
     * otherwise the user will be logged in during the next request.
     *
     * @codeCoverageIgnore
     *
     * @todo This can only to be tested in a browser test.
     */
    public function onSessionRotated(SessionWasRotated $event) :void
    {
        wp_set_auth_cookie($event->userId(), true, '', $event->newTokenRaw());
    }
    
}