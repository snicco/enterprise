<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\integration\Auth\TwoFactor\Infrastructure;

use Closure;
use Generator;
use RuntimeException;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\AuthBundle\Tests\fixtures\TwoFactorChallengeRepositoryInMemory;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeValidator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\Invalid2FaChallenge;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;

use function time;

final class TwoFactorChallengeRepositoryTest extends WPTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        TwoFactorChallengeRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'test_challenges');
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS test_challenges');
    }
    
    /**
     * @test
     *
     * @param  Closure(TestClock=):TwoFactorChallengeRepository $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_a_challenge_can_be_created_retrieved_and_destroyed(Closure $challenges) :void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)($clock = new TestClock());
        
        try {
            $challenges->get('foobar');
            throw new RuntimeException('An invalid token should thrown an exception');
        } catch (Invalid2FaChallenge $e) {
        }
        
        $now = time();
        
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, $now + 10);
        
        $challenges->store('selector', $challenge = new TwoFactorChallenge($hashed_validator, 1, $now + 10));
        
        $this->assertEquals($challenge, $challenges->get('selector'));
        
        $challenges->invalidate('selector');
        
        try {
            $challenges->get('selector');
            throw new RuntimeException('An invalidated token should thrown an exception');
        } catch (Invalid2FaChallenge $e) {
        }
    }
    
    /**
     * @test
     *
     * @param  Closure(TestClock=):TwoFactorChallengeRepository $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_expires_challenges_are_not_returned(Closure $challenges) :void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)($clock = new TestClock());
        
        $validator = new TwoFactorChallengeValidator('foo_secret');
        
        $now = time();
        
        $hashed_validator = $validator->getHashedValidator('validator', 1, $now + 1);
        
        $challenges->store('selector', $challenge = new TwoFactorChallenge($hashed_validator, 1, $now + 1));
        
        $clock->travelIntoFuture(1);
        
        $this->assertEquals($challenge, $challenges->get('selector'));
        
        $clock->travelIntoFuture(1);
        
        try {
            $challenges->get('selector');
            throw new RuntimeException('An expired token should thrown an exception');
        } catch (Invalid2FaChallenge $e) {
        }
    }
    
    /**
     * @test
     *
     * @param  Closure(TestClock=): TwoFactorChallengeRepository $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_expired_challenges_are_garbage_collected(Closure $challenges) :void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)($clock = new TestClock());
        
        $validator = new TwoFactorChallengeValidator('foo_secret');
        $now = $clock->currentTimestamp();
        
        $hashed_validator = $validator->getHashedValidator('validator1', 1, $now + 1);
        $challenges->store('selector1', $c1 = new TwoFactorChallenge($hashed_validator, 1, $now + 1));
        
        $hashed_validator = $validator->getHashedValidator('validator2', 2, $now + 2);
        $challenges->store('selector2', $c2 = new TwoFactorChallenge($hashed_validator, 2, $now + 2));
        
        $challenges->gc();
        
        $this->assertEquals($c1, $challenges->get('selector1'));
        $this->assertEquals($c2, $challenges->get('selector2'));
        
        $clock->travelIntoFuture(2);
    
        $this->assertEquals($c2, $challenges->get('selector2'));
        try {
            $challenges->get('selector1');
            throw new RuntimeException('First challenge should have been garbage collected');
        } catch (Invalid2FaChallenge $e) {
        }
        
        $clock->travelIntoFuture(1);
        
        try {
            $challenges->get('selector2');
            throw new RuntimeException('Second challenge should have been garbage collected');
        } catch (Invalid2FaChallenge $e) {
        }
    }
    
    public function twoFactorChallenges() :Generator
    {
        yield 'memory' => [fn(Clock $clock) => new TwoFactorChallengeRepositoryInMemory($clock)];
        yield 'db' => [
            fn(Clock $clock) => new TwoFactorChallengeRepositoryBetterWPDB(
                BetterWPDB::fromWpdb(),
                'test_challenges',
                $clock
            ),
        ];
    }
    
}