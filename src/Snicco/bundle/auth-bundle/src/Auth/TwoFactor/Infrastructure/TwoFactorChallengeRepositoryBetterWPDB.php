<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Infrastructure;

use Snicco\Component\TestableClock\Clock;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\TwoFactorChallenge;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\Invalid2FaChallenge;

final class TwoFactorChallengeRepositoryBetterWPDB implements TwoFactorChallengeRepository
{
    
    private BetterWPDB $db;
    
    /**
     * @var  non-empty-string
     */
    private string $table_name;
    private Clock $clock;
    
    /**
     * @param  non-empty-string  $table_name
     */
    public function __construct(BetterWPDB $db, string $table_name, Clock $clock = null)
    {
        $this->db = $db;
        $this->table_name = $table_name;
        $this->clock = $clock ? : SystemClock::fromUTC();
    }
    
    /**
     * @param  non-empty-string  $table_name
     */
    public static function createTable(BetterWPDB $db, string $table_name) :void
    {
        $db->unprepared(
            "CREATE TABLE IF NOT EXISTS `$table_name` (
            `selector` CHAR(24) NOT NULL,
            `hashed_validator` CHAR(64) NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `expires_at` INT(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`selector`)
        )"
        );
    }
    
    public function get(string $selector) :TwoFactorChallenge
    {
        $sql = "SELECT * FROM `$this->table_name` WHERE `selector` = ? AND `expires_at` >= ?";
        
        try {
            /** @var array{selector: string, hashed_validator: string, user_id: positive-int, expires_at: int} $row */
            $row = $this->db->selectRow($sql, [$selector, $this->clock->currentTimestamp()]);
        } catch (NoMatchingRowFound $e) {
            throw Invalid2FaChallenge::forSelector($selector);
        }
        
        return new TwoFactorChallenge(
            $row['hashed_validator'],
            $row['user_id'],
            $row['expires_at']
        );
    }
    
    public function invalidate(string $selector) :void
    {
        $count = $this->db->delete($this->table_name, ['selector' => $selector]);
        
        if (0 === $count) {
            throw Invalid2FaChallenge::forSelector($selector);
        }
    }
    
    public function store(string $selector, TwoFactorChallenge $challenge) :void
    {
        $this->db->insert($this->table_name, [
            'user_id' => $challenge->user_id,
            'expires_at' => $challenge->expires_at,
            'hashed_validator' => $challenge->hashed_validator,
            'selector' => $selector,
        ]);
    }
    
    public function gc() :void
    {
        $sql = "DELETE FROM `$this->table_name` WHERE `expires_at` < ?";
        
        $this->db->preparedQuery($sql, [$this->clock->currentTimestamp()]);
    }
    
}