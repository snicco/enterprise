<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authenticator;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Event\FailedPasswordAuthentication;
use Snicco\Enterprise\Bundle\Auth\User\InvalidPassword;
use Snicco\Enterprise\Bundle\Auth\User\UserNotFound;
use Snicco\Enterprise\Bundle\Auth\User\UserProvider;

final class PasswordAuthenticator extends Authenticator
{
    private UserProvider    $user_provider;

    private EventDispatcher $event_dispatcher;

    public function __construct(EventDispatcher $event_dispatcher, UserProvider $user_provider)
    {
        $this->user_provider = $user_provider;
        $this->event_dispatcher = $event_dispatcher;
    }

    public function attempt(Request $request, callable $next): LoginResult
    {
        if (! $this->canHandle($request)) {
            return $next($request);
        }

        $login_identifier = (string) $request->post('log');
        $password = (string) $request->post('pwd');

        try {
            $user = $this->user_provider->getUserByIdentifier($login_identifier);
        } catch (UserNotFound $e) {
            $this->event_dispatcher->dispatch(
                new FailedPasswordAuthentication((string) $request->ip(), $login_identifier)
            );

            return LoginResult::failed();
        }

        try {
            $this->user_provider->validatePassword($password, $user);
        } catch (InvalidPassword $e) {
            $this->event_dispatcher->dispatch(
                new FailedPasswordAuthentication((string) $request->ip(), $login_identifier)
            );

            return LoginResult::failed();
        }

        $remember = null;

        if ($request->has('remember_me')) {
            $remember = $request->boolean('remember_me');
        }

        return new LoginResult($user, $remember);
    }

    private function canHandle(Request $request): bool
    {
        if (! $request->isPost()) {
            return false;
        }

        return $request->filled(['pwd', 'log']);
    }
}
