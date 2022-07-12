<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\unit\Auth\TwoFactor\Domain;

use Codeception\Test\Unit;
use ParagonIE\ConstantTime\Binary;
use ReflectionProperty;
use RuntimeException;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\MalformedChallengeToken;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeExpired;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeWasTampered;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Fortress\Tests\fixtures\TwoFactorChallengeRepositoryInMemory;

use function array_key_first;
use function time;

/**
 * @internal
 */
final class TwoFactorChallengeServiceTest extends Unit
{
    /**
     * @test
     */
    public function that_a_challenge_can_be_created_and_retrieved(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            new TwoFactorChallengeRepositoryInMemory(),
            new TestClock()
        );

        $token = $service->createChallenge(1);

        $this->assertSame(1, $service->getChallengedUser($token));
    }

    /**
     * @test
     */
    public function that_an_invalid_token_length_throws_an_exception(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            new TwoFactorChallengeRepositoryInMemory(),
            new TestClock()
        );

        $token = $service->createChallenge(1);

        $this->expectException(MalformedChallengeToken::class);

        $service->getChallengedUser($token . 'a');
    }

    /**
     * @test
     */
    public function that_the_expiry_lifetime_is_taken_into_account(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            new TwoFactorChallengeRepositoryInMemory(),
            $clock = new TestClock()
        );

        $token = $service->createChallenge(1, 200);

        $this->assertSame(1, $service->getChallengedUser($token));

        $clock->travelIntoFuture(200);

        $this->assertSame(1, $service->getChallengedUser($token));

        $clock->travelIntoFuture(1);

        $this->expectException(TwoFactorChallengeExpired::class);

        $service->getChallengedUser($token);
    }

    /**
     * @test
     */
    public function that_a_tampered_user_id_will_throw_an_exception(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            $repo = new TwoFactorChallengeRepositoryInMemory(),
        );

        $token = $service->createChallenge(1, 200);

        $this->withATamperedUserId($repo, 2);

        $this->expectException(TwoFactorChallengeWasTampered::class);

        $service->getChallengedUser($token);
    }

    /**
     * @test
     */
    public function that_a_tampered_expiry_timestamp_will_throw_an_exception(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            $repo = new TwoFactorChallengeRepositoryInMemory(),
        );

        $token = $service->createChallenge(1, 200);

        $this->withATamperedExpiryTimestamp($repo, time() + 300);

        $this->expectException(TwoFactorChallengeWasTampered::class);

        $service->getChallengedUser($token);
    }

    /**
     * @test
     */
    public function that_a_tampered_hash_will_throw_an_exception(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            $repo = new TwoFactorChallengeRepositoryInMemory(),
        );

        $token = $service->createChallenge(1, 200);

        $this->withATamperedHash($repo, 'foo_bar');

        $this->expectException(TwoFactorChallengeWasTampered::class);

        $service->getChallengedUser($token);
    }

    /**
     * @test
     */
    public function that_rotating_the_hmac_secret_will_throw_an_exception(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            $repo = new TwoFactorChallengeRepositoryInMemory(),
        );
        $token = $service->createChallenge(1, 200);

        $service = new TwoFactorChallengeService(
            'other_secret',
            $repo
        );

        $this->expectException(TwoFactorChallengeWasTampered::class);

        $service->getChallengedUser($token);
    }

    /**
     * @test
     */
    public function that_a_tampered_token_will_be_removed(): void
    {
        $service = new TwoFactorChallengeService(
            'secret',
            $repo = new TwoFactorChallengeRepositoryInMemory(),
        );

        $token = $service->createChallenge(1, 200);

        $this->withATamperedUserId($repo, 2);

        $selector = Binary::safeSubstr($token, 0, 32);
        $repo->get($selector);

        try {
            $service->getChallengedUser($token);

            throw new RuntimeException('Should have thrown exception');
        } catch (TwoFactorChallengeWasTampered $e) {
        }

        $this->expectException(CouldNotFindChallengeToken::class);
        $repo->get($selector);
    }

    private function withATamperedUserId(TwoFactorChallengeRepositoryInMemory $repo, int $new_user_id): void
    {
        $challenges_prop = new ReflectionProperty($repo, 'challenges');
        $challenges_prop->setAccessible(true);

        /** @var array<string,TwoFactorChallenge> $challenges */
        $challenges = $challenges_prop->getValue($repo);
        $first = $challenges[(string) array_key_first($challenges)];
        $this->assertInstanceOf(TwoFactorChallenge::class, $first);

        $user_id_prop = new ReflectionProperty($first, 'user_id');
        $user_id_prop->setAccessible(true);
        $user_id_prop->setValue($first, $new_user_id);

        $challenges_prop->setAccessible(false);
        $user_id_prop->setAccessible(false);
    }

    private function withATamperedExpiryTimestamp(
        TwoFactorChallengeRepositoryInMemory $repo,
        int $new_timestamp
    ): void {
        $challenges_prop = new ReflectionProperty($repo, 'challenges');
        $challenges_prop->setAccessible(true);

        /** @var array<string,TwoFactorChallenge> $challenges */
        $challenges = $challenges_prop->getValue($repo);
        $first = $challenges[(string) array_key_first($challenges)];
        $this->assertInstanceOf(TwoFactorChallenge::class, $first);

        $user_id_prop = new ReflectionProperty($first, 'expires_at');
        $user_id_prop->setAccessible(true);
        $user_id_prop->setValue($first, $new_timestamp);

        $challenges_prop->setAccessible(false);
        $user_id_prop->setAccessible(false);
    }

    private function withATamperedHash(TwoFactorChallengeRepositoryInMemory $repo, string $new_hash): void
    {
        $challenges_prop = new ReflectionProperty($repo, 'challenges');
        $challenges_prop->setAccessible(true);

        /** @var array<string,TwoFactorChallenge> $challenges */
        $challenges = $challenges_prop->getValue($repo);
        $first = $challenges[(string) array_key_first($challenges)];
        $this->assertInstanceOf(TwoFactorChallenge::class, $first);

        $user_id_prop = new ReflectionProperty($first, 'hashed_validator');
        $user_id_prop->setAccessible(true);
        $user_id_prop->setValue($first, $new_hash);

        $challenges_prop->setAccessible(false);
        $user_id_prop->setAccessible(false);
    }
}
