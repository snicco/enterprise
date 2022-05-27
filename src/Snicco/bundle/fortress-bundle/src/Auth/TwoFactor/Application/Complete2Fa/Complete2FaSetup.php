<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Complete2Fa;

/**
 * @psalm-readonly
 */
final class Complete2FaSetup
{
    public int $user_id;

    public string $otp_code;

    /**
     * @param positive-int $user_id
     */
    public function __construct(int $user_id, string $otp_code)
    {
        $this->user_id = $user_id;
        $this->otp_code = $otp_code;
    }
}
