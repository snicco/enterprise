<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Password;

use ZxcvbnPhp\Zxcvbn;
use ParagonIE\ConstantTime\Binary;
use Snicco\Enterprise\Bundle\Auth\Password\Exception\PasswordLengthExceeded;
use Snicco\Enterprise\Bundle\Auth\Password\Exception\InsufficientPasswordLength;
use Snicco\Enterprise\Bundle\Auth\Password\Exception\InsufficientPasswordEntropy;

final class PasswordPolicy
{
    
    private Zxcvbn $zxcvbn;
    
    public function __construct() {
        $this->zxcvbn = new Zxcvbn();
    }
    
    /**
     * @param  string[]  $context
     */
    public function check(string $plain_text_password, array $context = [])  :void
    {
        $length = Binary::safeStrlen($plain_text_password);
        
        if($length < 12) {
            throw new InsufficientPasswordLength("Passwords must have at least 12 characters.");
        }
        
        if($length > 4096) {
            throw new PasswordLengthExceeded("Password can not have more than 4096 characters");
        }
    
        /** @var array{score: int} $check */
        $check = $this->zxcvbn->passwordStrength($plain_text_password, $context);
        
        if($check['score'] < 3) {
            throw new InsufficientPasswordEntropy();
        }
        
    }
    
}