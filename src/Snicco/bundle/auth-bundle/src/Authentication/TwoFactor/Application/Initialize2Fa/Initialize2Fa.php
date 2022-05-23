<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\Initialize2Fa;

/**
 * @psalm-readonly
 */
final class Initialize2Fa
{
    
    /**
     * @var positive-int
     */
    public int $user_id;
    
    /**
     * @var non-empty-list<non-empty-string>
     */
    public array $backup_codes;
    
    /**
     * @var non-empty-string
     */
    public string $secret_key_plain_text;
    
    /**
     * @param  positive-int  $user_id
     * @param  non-empty-string  $secret_key_plain_text
     * @param  non-empty-list<non-empty-string>  $backup_codes
     */
    public function __construct(int $user_id, string $secret_key_plain_text, array $backup_codes)
    {
        $this->user_id = $user_id;
        $this->backup_codes = $backup_codes;
        $this->secret_key_plain_text = $secret_key_plain_text;
    }
    
}
