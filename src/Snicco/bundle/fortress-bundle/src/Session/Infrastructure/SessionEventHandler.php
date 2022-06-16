<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure;

use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\Event\SessionWasRotated;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\MappedEvent\AuthCookieValid;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\MappedEvent\SetLoginCookie;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\MappedEvent\WPLogout;

use function add_filter;
use function is_ssl;
use function is_string;
use function remove_filter;
use function setcookie;
use function time;
use function wp_clear_auth_cookie;
use function wp_logout;
use function wp_set_auth_cookie;

use const AUTH_COOKIE;
use const COOKIE_DOMAIN;
use const LOGGED_IN_COOKIE;
use const PHP_INT_MAX;
use const PLUGINS_COOKIE_PATH;
use const SECURE_AUTH_COOKIE;

final class SessionEventHandler implements EventSubscriber
{
    private SessionManager $session_manager;

    /**
     * @var array<string,true>
     */
    private array $updated = [];

    private string $remember_me_cookie_name;

    public function __construct(SessionManager $session_manager, string $remember_me_cookie_name)
    {
        $this->session_manager = $session_manager;
        $this->remember_me_cookie_name = $remember_me_cookie_name;
    }

    public static function subscribedEvents(): array
    {
        return [
            SessionWasIdle::class => 'onSessionIdle',
            SessionWasRotated::class => 'onSessionRotated',
            AuthCookieValid::class => 'onAuthCookieValid',
            SetLoginCookie::class => 'onSetLoginCookie',
            WPLogout::class => 'onWPLogout',
        ];
    }

    /**
     * If a session is idle due to lack of activity we need to explicitly clear
     * the auth cookies, in order to not keep sending the same idle cookie in
     * the frontend.
     *
     * In the admin area users are automatically logged auth by auth_redirect().
     *
     * We cant use {@see wp_clear_auth_cookie()} here because that function will
     * try to get the current user which will lead to a SEGFAULT.
     *
     * @codeCoverageIgnore
     *
     * @todo Browser tests
     */
    public function onSessionIdle(SessionWasIdle $event): void
    {
        /**
         * @var string $cookie_domain
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
     * @todo Browser tests
     */
    public function onSessionRotated(SessionWasRotated $event): void
    {
        $remember = isset($_COOKIE[$this->remember_me_cookie_name]);

        $callback = $this->ensureSessionExpirationStaysTheSame($event->expires_at);

        try {
            wp_set_auth_cookie($event->user_id, $remember, '', $event->new_token_plain);
        } finally {
            $this->resetSessionExpirationFilter($callback);
        }
    }

    public function onAuthCookieValid(AuthCookieValid $event): void
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
     * @see wp_set_auth_cookie()
     *
     * @codeCoverageIgnore
     *
     * @todo Browser tests
     */
    public function onSetLoginCookie(SetLoginCookie $event): void
    {
        if ($event->userWantsToBeRemembered()) {
            $cookie_domain = $this->cookieDomain();
            setcookie($this->remember_me_cookie_name, '1', [
                'expires' => $event->cookie_expiration,
                'path' => COOKIEPATH,
                'domain' => $cookie_domain,
                'secure' => is_ssl(),
                'httponly' => true,
            ]);
            if (COOKIEPATH !== SITECOOKIEPATH) {
                setcookie($this->remember_me_cookie_name, '1', [
                    'expires' => $event->cookie_expiration,
                    'path' => SITECOOKIEPATH,
                    'domain' => $cookie_domain,
                    'secure' => is_ssl(),
                    'httponly' => true,
                ]);
            }
        }
    }

    /**
     * @see wp_logout()
     *
     * @codeCoverageIgnore
     *
     * @todo Browser tests
     */
    public function onWPLogout(WPLogout $event): void
    {
        $remember = isset($_COOKIE[$this->remember_me_cookie_name]);

        if ($remember) {
            $cookie_domain = $this->cookieDomain();
            setcookie($this->remember_me_cookie_name, 'deleted', [
                'expires' => 1,
                'path' => COOKIEPATH,
                'domain' => $cookie_domain,
                'secure' => is_ssl(),
                'httponly' => true,
            ]);
            if (COOKIEPATH !== SITECOOKIEPATH) {
                setcookie($this->remember_me_cookie_name, 'deleted', [
                    'expires' => 1,
                    'path' => SITECOOKIEPATH,
                    'domain' => $cookie_domain,
                    'secure' => is_ssl(),
                    'httponly' => true,
                ]);
            }
        }
    }

    private function cookieDomain(): string
    {
        /** @var string|false $cookie_domain */
        $cookie_domain = COOKIE_DOMAIN;

        return is_string($cookie_domain) ? $cookie_domain : '';
    }

    private function ensureSessionExpirationStaysTheSame(int $expires_at): callable
    {
        add_filter('auth_cookie_expiration', $cb = fn (): int => $expires_at - time(), PHP_INT_MAX);

        return $cb;
    }

    private function resetSessionExpirationFilter(callable $callback): void
    {
        remove_filter('auth_cookie_expiration', $callback, PHP_INT_MAX);
    }
}
