<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Domain;

use Snicco\Component\HttpRouting\Http\Psr7\Request;

abstract class Authenticator
{
    /**
     * @param callable(Request):LoginResult $next
     */
    abstract public function attempt(Request $request, callable $next): LoginResult;
}
