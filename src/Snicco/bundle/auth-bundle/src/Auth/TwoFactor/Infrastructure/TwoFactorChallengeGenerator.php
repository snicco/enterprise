<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure;

use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeValidator;

use function time;
use function intval;
use function urlencode;
use function random_bytes;

final class TwoFactorChallengeGenerator
{
    private TwoFactorChallengeRepository $challenges;
    private TwoFactorChallengeValidator $validator;
    
    private const SELECTOR_BYTES = 16;
    
    public function __construct(TwoFactorChallengeRepository $challenges, TwoFactorChallengeValidator $validator)
    {
        $this->challenges = $challenges;
        $this->validator = $validator;
    }
    
    public function urlToken(int $user_id) :string
    {
        $selector = Base64UrlSafe::encode(random_bytes(self::SELECTOR_BYTES));
        $verifier = Base64UrlSafe::encode(random_bytes(32));
        
        $expires_at = time() + 300;
        
        $this->challenges->store(
            $selector,
            new TwoFactorChallenge(
                $this->validator->getHashedValidator($verifier, $user_id, $expires_at),
                $user_id,
                $expires_at
            )
        );
        
        return $selector.$verifier;
    }
    
    /**
     * @return array{0:string, 1:string}
     */
    public function splitUserToken(string $url_token_user_provided) :array
    {
        $length = Binary::safeStrlen($url_token_user_provided);
        
        $selector_length_base64 = intval(( (float) self::SELECTOR_BYTES * 1.5) );
        
        return [
            Binary::safeSubstr($url_token_user_provided, 0, $selector_length_base64),
            Binary::safeSubstr($url_token_user_provided, -($length-$selector_length_base64)),
        ];
    }
    
}