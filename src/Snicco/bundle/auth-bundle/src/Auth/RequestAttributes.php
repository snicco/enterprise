<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth;

final class RequestAttributes
{
    /**
     * @var string
     */
    public const CHALLENGED_USER = '_' . self::class . ':challenged_user';

    /**
     * @var string
     */
    public const REMEMBER_CHALLENGED_USER = '_' . self::class . ':remember_challenged_user';
}
