<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Auth\TwoFactor\Application\Delete2Fa;

/**
 * @psalm-readonly
 */
final class Delete2FaSettings
{
    /**
     * @var positive-int
     */
    public int $user_id;

    /**
     * @param positive-int $user_id
     */
    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }
}
