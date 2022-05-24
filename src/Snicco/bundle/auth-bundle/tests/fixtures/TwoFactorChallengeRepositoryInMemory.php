<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\fixtures;

use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\Invalid2FaChallenge;

use function array_filter;

final class TwoFactorChallengeRepositoryInMemory implements TwoFactorChallengeRepository
{
    
    /**
     * @var array<string,TwoFactorChallenge>
     */
    private array $challenges = [];
    
    private Clock $clock;
    
    public function __construct(Clock $clock = null) {
        $this->clock = $clock ?: SystemClock::fromUTC();
    }
    
    public function get(string $selector) :TwoFactorChallenge
    {
        if ( ! isset($this->challenges[$selector])) {
            throw Invalid2FaChallenge::forSelector($selector);
        }
        $challenge = $this->challenges[$selector];
        
        if($challenge->expires_at < $this->clock->currentTimestamp()){
            unset($this->challenges[$selector]);
            throw Invalid2FaChallenge::forSelector($selector);
        }
        
        return $challenge;
        
    }
    
    public function invalidate(string $selector) :void
    {
        if ( ! isset($this->challenges[$selector])) {
            throw Invalid2FaChallenge::forSelector($selector);
        }
        unset($this->challenges[$selector]);
    }
    
    public function store(string $selector, TwoFactorChallenge $challenge) :void
    {
        $this->challenges[$selector] = $challenge;
    }
    
    public function gc() :void
    {
        $this->challenges = array_filter(
            $this->challenges,
            fn(TwoFactorChallenge $challenge) => $challenge->expires_at >= $this->clock->currentTimestamp()
        );
    }
    
}