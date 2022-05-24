<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Application\ResetBackupCodes;

/**
 * @psalm-immutable
 */
final class ResetBackupCodes
{
    
    /**
     * @var positive-int
     */
    public int $user_id;
    
    /**
     * @var non-empty-list<non-empty-string>
     */
    public array $new_codes;
    
    /**
     * @param  positive-int  $user_id
     * @param  non-empty-list<non-empty-string>  $new_codes
     */
    public function __construct(int $user_id, array $new_codes)
    {
        $this->user_id = $user_id;
        $this->new_codes = $new_codes;
    }
    
}
