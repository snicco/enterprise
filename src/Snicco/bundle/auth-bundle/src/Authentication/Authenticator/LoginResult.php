<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use WP_User;

final class LoginResult
{
    private ?WP_User           $user;

    private ?bool              $remember_user;

    private ?ResponseInterface $response;

    public function __construct(WP_User $user = null, bool $remember_user = null, ResponseInterface $response = null)
    {
        $this->user = $user;
        $this->remember_user = $remember_user;
        $this->response = $response;
    }

    public function isSuccess(): bool
    {
        return $this->user instanceof WP_User;
    }

    public function rememberUser(): ?bool
    {
        return $this->remember_user;
    }

    public function response(): ?ResponseInterface
    {
        return $this->response;
    }

    public function authenticatedUser(): WP_User
    {
        if (! $this->user instanceof WP_User) {
            throw new LogicException('No user was authenticated. User isSuccess() before calling this method.');
        }

        return $this->user;
    }

    public static function failed(?Response $response = null): self
    {
        return new self(null, null, $response);
    }
}
