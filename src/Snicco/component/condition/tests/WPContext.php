<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests;

/** @psalm-suppress InvalidArrayOffset */
final class WPContext
{
    public static function resetAll(): void
    {
        unset($GLOBALS['current_screen']);
    }

    public static function forceIsAdmin(bool $admin = true): void
    {
        $stub = new class($admin) {
            private bool $admin;

            public function __construct(bool $admin)
            {
                $this->admin = $admin;
            }

            public function in_admin(): bool
            {
                return $this->admin;
            }
        };

        $GLOBALS['current_screen'] = $stub;
    }
}
