<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain;

use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;

interface TwoFactorChallengeRepository
{
    /**
     * This interface MUST NOT return challenges that are expired.
     *
     * @throws CouldNotFindChallengeToken
     */
    public function get(string $selector): TwoFactorChallenge;

    /**
     * @throws CouldNotFindChallengeToken
     */
    public function destroy(string $selector): void;

    public function store(string $selector, TwoFactorChallenge $challenge): void;

    public function gc(): void;
}
