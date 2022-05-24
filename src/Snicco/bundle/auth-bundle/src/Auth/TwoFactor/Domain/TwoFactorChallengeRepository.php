<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\Invalid2FaChallenge;

interface TwoFactorChallengeRepository
{
    /**
     * @throws Invalid2FaChallenge
     */
    public function get(string $selector) :TwoFactorChallenge;
    
    /**
     * @throws Invalid2FaChallenge
     */
    public function invalidate(string $selector) :void;
    
    public function store(string $selector, TwoFactorChallenge $challenge) :void;
    
    public function gc() :void;
    
}