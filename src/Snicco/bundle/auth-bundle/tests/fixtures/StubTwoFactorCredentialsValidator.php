<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\fixtures;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\TwoFactor\InvalidTwoFactorCredentials;
use Snicco\Enterprise\Bundle\Auth\TwoFactor\TwoFactorCredentialsValidator;
use WP_User;

final class StubTwoFactorCredentialsValidator implements TwoFactorCredentialsValidator
{
    public function validate(Request $request, WP_User $user): void
    {
        if ($request->boolean('succeed_2fa')) {
            return;
        }

        throw new InvalidTwoFactorCredentials('Stub TwoFactorProvider force failed check.');
    }
}
