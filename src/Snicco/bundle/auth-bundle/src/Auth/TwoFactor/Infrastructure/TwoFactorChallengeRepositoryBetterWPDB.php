<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\CouldNotFindChallengeToken;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use function sprintf;

final class TwoFactorChallengeRepositoryBetterWPDB implements TwoFactorChallengeRepository
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    private Clock $clock;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(BetterWPDB $db, string $table_name, Clock $clock = null)
    {
        $this->db = $db;
        $this->table_name = $table_name;
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    /**
     * @param non-empty-string $table_name
     */
    public static function createTable(BetterWPDB $db, string $table_name): void
    {
        $db->unprepared(
            "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `selector` CHAR(32) NOT NULL,
            `hashed_validator` CHAR(64) NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `expires_at` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`selector`)
        )"
        );
    }

    public function get(string $selector): TwoFactorChallenge
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE `selector` = ?', $this->table_name);

        try {
            /** @var array{selector: string, hashed_validator: string, user_id: positive-int, expires_at: int} $row */
            $row = $this->db->selectRow($sql, [$selector]);
        } catch (NoMatchingRowFound $e) {
            throw CouldNotFindChallengeToken::forSelector($selector);
        }

        return new TwoFactorChallenge(
            $row['hashed_validator'],
            $row['user_id'],
            $row['expires_at']
        );
    }

    public function destroy(string $selector): void
    {
        $count = $this->db->delete($this->table_name, [
            'selector' => $selector,
        ]);

        if (0 === $count) {
            throw CouldNotFindChallengeToken::forSelector($selector);
        }
    }

    public function store(string $selector, TwoFactorChallenge $challenge): void
    {
        $this->db->insert($this->table_name, [
            'user_id' => $challenge->user_id,
            'expires_at' => $challenge->expires_at,
            'hashed_validator' => $challenge->hashed_validator,
            'selector' => $selector,
        ]);
    }

    public function gc(): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('DELETE FROM `%s` WHERE `expires_at` < ?', $this->table_name);

        $this->db->preparedQuery($sql, [$this->clock->currentTimestamp()]);
    }
}
