<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\Authenticator\Domain;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Auth\Authenticator\Domain\LoginResult;

abstract class Authenticator
{
    /**
     * @param callable(Request):LoginResult $next
     */
    abstract public function attempt(Request $request, callable $next): LoginResult;
}
