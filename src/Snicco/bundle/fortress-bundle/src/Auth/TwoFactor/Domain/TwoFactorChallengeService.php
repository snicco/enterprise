<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain;

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;
use RuntimeException;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\MalformedChallengeToken;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeExpired;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorChallengeWasTampered;
use Webmozart\Assert\Assert;

use function abs;
use function hash_equals;
use function hash_hmac;
use function json_encode;
use function random_bytes;

use const JSON_THROW_ON_ERROR;

final class TwoFactorChallengeService
{
    /**
     * @var int
     */
    private const SELECTOR_BYTES = 24;

    /**
     * @var int
     */
    private const VALIDATOR_BYTES = 33;

    /**
     * @var int
     */
    private const SELECTOR_LENGTH = (self::SELECTOR_BYTES * 4 / 3);

    /**
     * @var int
     */
    private const TOKEN_TOTAL_LENGTH = (self::SELECTOR_BYTES * 4 / 3 + self::VALIDATOR_BYTES * 4 / 3);

    private string $hmac_key;

    private TwoFactorChallengeRepository $challenge_repository;

    private Clock $clock;

    public function __construct(
        string $hmac_key,
        TwoFactorChallengeRepository $challenge_repository,
        Clock $clock = null
    ) {
        $this->hmac_key = $hmac_key;
        $this->challenge_repository = $challenge_repository;
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    public function createChallenge(int $user_id, int $lifetime_in_seconds = 300): string
    {
        $selector = Base64UrlSafe::encode(random_bytes(self::SELECTOR_BYTES));
        $verifier = Base64UrlSafe::encode(random_bytes(self::VALIDATOR_BYTES));

        $expires_at = $this->clock->currentTimestamp() + $lifetime_in_seconds;

        $this->challenge_repository->store(
            $selector,
            new TwoFactorChallenge(
                $this->getHashedValidator(
                    $verifier,
                    $user_id,
                    $expires_at
                ),
                $user_id,
                $expires_at
            )
        );

        return $selector . $verifier;
    }

    /**
     * @throws CouldNotFindChallengeToken
     * @throws MalformedChallengeToken
     * @throws TwoFactorChallengeExpired
     * @throws TwoFactorChallengeWasTampered
     *
     * @return positive-int
     */
    public function getChallengedUser(string $token): int
    {
        if (self::TOKEN_TOTAL_LENGTH !== Binary::safeStrlen($token)) {
            throw MalformedChallengeToken::becauseOfIncorrectLength();
        }

        [$selector, $verifier] = $this->splitUserToken($token);

        $challenge = $this->challenge_repository->get($selector);

        $valid = $this->isValid($verifier, $challenge);

        if (! $valid) {
            $this->challenge_repository->destroy($selector);

            throw TwoFactorChallengeWasTampered::forSelector($selector);
        }

        $lifetime_left = $challenge->expires_at - $this->clock->currentTimestamp();

        if ($lifetime_left < 0) {
            throw TwoFactorChallengeExpired::forSelector($selector, abs($lifetime_left));
        }

        return $challenge->user_id;
    }
    
    public function invalidate(string $challenge_id) :void
    {
        $this->challenge_repository->destroy(
            $this->splitUserToken($challenge_id)[0]
        );
    }
    
    private function getHashedValidator(string $plain_text_validator, int $user_id, int $expires_at): string
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

    /**
     * @return array{0:string, 1:string}
     */
    private function splitUserToken(string $url_token_user_provided): array
    {
        $selector_length = self::SELECTOR_LENGTH;
        Assert::integer($selector_length);

        return [
            Binary::safeSubstr($url_token_user_provided, 0, $selector_length),
            Binary::safeSubstr($url_token_user_provided, $selector_length),
        ];
    }

    private function isValid(string $user_provided_validator_plain_text, TwoFactorChallenge $challenge): bool
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
