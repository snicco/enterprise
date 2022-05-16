<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\TwoFactor;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use WP_User;

interface TwoFactorCredentialsValidator
{
    /**
     * @throws InvalidTwoFactorCredentials
     */
    public function validate(Request $request, WP_User $user): void;
}
