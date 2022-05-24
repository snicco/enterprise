<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain;

use RuntimeException;

use function hash_hmac;
use function json_encode;

use function hash_equals;

use function base64_encode;

use const JSON_THROW_ON_ERROR;

final class TwoFactorChallengeValidator
{
    
    private string $hmac_key;
    
    public function __construct(string $hmac_key)
    {
        $this->hmac_key = $hmac_key;
    }
    
    public function getHashedValidator(string $plain_text_validator, int $user_id, int $expires_at) :string
    {
        $json = json_encode([
            $plain_text_validator,
            $user_id,
            $expires_at,
        ], JSON_THROW_ON_ERROR);
        
        $res = hash_hmac('sha256', $json, $this->hmac_key);
        
        if (false === $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('hash_hmac returned false.');
            // @codeCoverageIgnoreEnd
        }
        
        return $res;
    }
    
    public function isValid(string $user_provided_validator_plain_text, TwoFactorChallenge $challenge) :bool
    {
        return hash_equals(
            $challenge->hashed_validator,
            $this->getHashedValidator(
                $user_provided_validator_plain_text,
                $challenge->user_id,
                $challenge->expires_at
            ),
        );
    }
    
}