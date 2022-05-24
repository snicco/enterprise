<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\unit\Auth\TwoFactor\Domain;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\TwoFactorChallengeValidator;

use function time;

final class TwoFactorChallengeValidatorTest extends Unit
{
    
    /**
     * @test
     */
    public function that_a_challenge_can_be_validated() :void
    {
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, time() + 10);
        
        // Challenge comes from a query to the database or some other storage.
        $challenge = new TwoFactorChallenge($hashed_validator, 1, time() + 10);
        
        $this->assertTrue($validator->isValid('validator', $challenge));
    }
    
    /**
     * @test
     */
    public function that_changing_the_plain_text_validator_returns_false() :void
    {
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, time() + 10);
        
        // Challenge comes from a query to the database or some other storage.
        $challenge = new TwoFactorChallenge($hashed_validator,1, time() + 10);
        
        $this->assertFalse(
            $validator->isValid( 'validator-changed', $challenge)
        );
    }
    
    /**
     * @test
     */
    public function that_changing_the_user_id_returns_false() :void
    {
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, time() + 10);
        
        // Challenge comes from a query to the database or some other storage.
        $challenge = new TwoFactorChallenge($hashed_validator, 2, time() + 10);
        
        $this->assertFalse(
            $validator->isValid('validator', $challenge)
        );
    }
    
    /**
     * @test
     */
    public function that_changing_the_expiration_time_returns_false() :void
    {
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, time() + 10);
        
        // Challenge comes from a query to the database or some other storage.
        $challenge = new TwoFactorChallenge($hashed_validator, 1, time() + 11);
        
        $this->assertFalse(
            $validator->isValid('validator', $challenge)
        );
    }
    
    /**
     * @test
     */
    public function that_changing_the_signing_secret_returns_false() :void
    {
        $validator = new TwoFactorChallengeValidator('foo_secret');
    
        $hashed_validator = $validator->getHashedValidator('validator', 1, time() + 10);
    
        // Challenge comes from a query to the database or some other storage.
        $challenge = new TwoFactorChallenge($hashed_validator,1, time() + 10);
    
        $validator = new TwoFactorChallengeValidator('bar_secret');
        
        $this->assertFalse(
            $validator->isValid( 'validator', $challenge)
        );
    }
    
}