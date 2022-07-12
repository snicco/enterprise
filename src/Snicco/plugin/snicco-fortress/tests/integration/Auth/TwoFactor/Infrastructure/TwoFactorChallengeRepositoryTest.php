<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\integration\Auth\TwoFactor\Infrastructure;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Fortress\Tests\fixtures\TwoFactorChallengeRepositoryInMemory;
use Webmozart\Assert\Assert;

use function time;
use function wp_create_user;

/**
 * @internal
 */
final class TwoFactorChallengeRepositoryTest extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TwoFactorChallengeRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'test_challenges');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS test_challenges');
    }

    /**
     * @test
     *
     * @param  Closure(TestClock=) $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_a_challenge_can_be_created_retrieved_and_destroyed(Closure $challenges): void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)(new TestClock());

        try {
            $challenges->get('foobar');

            throw new RuntimeException('An invalid token should thrown an exception');
        } catch (CouldNotFindChallengeToken $e) {
        }

        $now = time();

        $challenges->store('selector', $challenge = new TwoFactorChallenge('selector_hashed', 1, $now + 10));

        $this->assertEquals($challenge, $challenges->get('selector'));

        $challenges->destroy('selector');

        try {
            $challenges->get('selector');

            throw new RuntimeException('An invalidated token should thrown an exception');
        } catch (CouldNotFindChallengeToken $e) {
        }
    }

    /**
     * @test
     *
     * @param  Closure(TestClock=) $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_expires_challenges_are_returned(Closure $challenges): void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)($clock = new TestClock());

        $now = time();

        $challenges->store('selector', $challenge = new TwoFactorChallenge('selector_hashed', 1, $now + 1));

        $clock->travelIntoFuture(1);

        $this->assertEquals($challenge, $challenges->get('selector'));

        $clock->travelIntoFuture(1);

        $challenges->get('selector');
    }

    /**
     * @test
     *
     * @param  Closure(TestClock=) $challenges
     *
     * @dataProvider twoFactorChallenges
     */
    public function that_expired_challenges_are_garbage_collected(Closure $challenges): void
    {
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = ($challenges)($clock = new TestClock());

        $now = $clock->currentTimestamp();

        $other_id = wp_create_user('foo', 'bar');
        Assert::positiveInteger($other_id);

        $challenges->store('selector1', $c1 = new TwoFactorChallenge('hashed1', 1, $now + 1));
        $challenges->store('selector2', $c2 = new TwoFactorChallenge('hashed2', $other_id, $now + 2));

        $challenges->gc();

        $this->assertEquals($c1, $challenges->get('selector1'));
        $this->assertEquals($c2, $challenges->get('selector2'));

        $clock->travelIntoFuture(2);

        $challenges->gc();

        $this->assertEquals($c2, $challenges->get('selector2'));

        try {
            $challenges->get('selector1');

            throw new RuntimeException('First challenge should have been garbage collected');
        } catch (CouldNotFindChallengeToken $e) {
        }

        $clock->travelIntoFuture(1);

        $challenges->gc();

        try {
            $challenges->get('selector2');

            throw new RuntimeException('Second challenge should have been garbage collected');
        } catch (CouldNotFindChallengeToken $e) {
        }
    }

    public function twoFactorChallenges(): Generator
    {
        yield 'memory' => [
            fn (Clock $clock): TwoFactorChallengeRepositoryInMemory => new TwoFactorChallengeRepositoryInMemory($clock),
        ];
        yield 'db' => [
            fn (Clock $clock): TwoFactorChallengeRepositoryBetterWPDB => new TwoFactorChallengeRepositoryBetterWPDB(
                BetterWPDB::fromWpdb(),
                'test_challenges',
                $clock
            ),
        ];
    }
}
