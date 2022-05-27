<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Session\Infrastructure;

use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Enterprise\AuthBundle\Session\Domain\AuthSession;
use Snicco\Enterprise\AuthBundle\Session\Domain\Exception\InvalidSessionToken;
use Snicco\Enterprise\AuthBundle\Session\Domain\SessionRepository;

use function base64_decode;
use function base64_encode;
use function gettype;
use function is_array;
use function serialize;
use function sprintf;
use function unserialize;

final class SessionRepositoryBetterWPDB implements SessionRepository
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    /**
     * @var array<string,AuthSession>
     */
    private array $session_cache = [];

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

    public static function createTable(BetterWPDB $db, string $table_name): void
    {
        $users_table = $GLOBALS['wpdb']->users;
    
        $db->unprepared(
            "CREATE TABLE IF NOT EXISTS `{$table_name}`  (
            	`id` INTEGER(11) NOT NULL AUTO_INCREMENT,
                `hashed_token` CHAR(64) NOT NULL,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `payload` TEXT NOT NULL,
                `expires_at` INTEGER(11) UNSIGNED NOT NULL,
                `last_activity` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `last_rotation` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `created_at` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES $users_table(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE KEY (`hashed_token`),
                KEY (`expires_at`)
        );"
        );
    }

    public function save(AuthSession $session): void
    {
        $data = $session->data();
        $hashed_token = $session->hashedToken();

        $payload = $this->encodePayload($data);

        if ($this->exists($hashed_token)) {
            $this->db->update($this->table_name, [
                'hashed_token' => $hashed_token,
            ], [
                'payload' => $payload,
                'last_activity' => $this->clock->currentTimestamp(),
            ]);
            unset($this->session_cache[$hashed_token]);

            return;
        }

        $this->db->insert($this->table_name, [
            'hashed_token' => $hashed_token,
            'user_id' => $session->userId(),
            'payload' => $payload,
            'expires_at' => $session->expiresAt(),
        ]);

        $this->session_cache[$session->hashedToken()] = $session;
    }

    public function delete(string $hashed_token): void
    {
        unset($this->session_cache[$hashed_token]);

        $affected = $this->db->delete($this->table_name, [
            'hashed_token' => $hashed_token,
        ]);

        if (0 === $affected) {
            throw InvalidSessionToken::forToken($hashed_token);
        }
    }

    public function getSession(string $hashed_token): AuthSession
    {
        if (! isset($this->session_cache[$hashed_token])) {
            try {
                $session = $this->querySession($hashed_token);
                $this->session_cache[$hashed_token] = $session;

                return $session;
            } catch (NoMatchingRowFound $e) {
                throw InvalidSessionToken::forToken($hashed_token);
            }
        }

        $cached_session = $this->session_cache[$hashed_token];

        if ($this->isExpired($cached_session)) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        return $cached_session;
    }

    public function getAllForUser(int $user_id): array
    {
        /** @var non-empty-string $sql */
        $sql = sprintf(
            'select `payload`, `hashed_token`, `last_activity`, `last_rotation`, `user_id`, `expires_at`
                    from %s
                    where `user_id` = ?
                    and `expires_at` >= ?',
            $this->table_name
        );

        /**
         * @var array<array{
         *     payload:string,
         *     hashed_token: string,
         *     last_activity: int,
         *     last_rotation: int,
         *     user_id: int,
         *     expires_at: int
         * }> $rows
         */
        $rows = $this->db->selectAll($sql, [$user_id, $this->clock->currentTimestamp()]);

        $sessions = [];

        foreach ($rows as $row) {
            $session = new AuthSession(
                $row['hashed_token'],
                $row['user_id'],
                $row['last_activity'],
                $row['last_rotation'],
                $this->decodePayload($row['payload'])
            );
            $sessions[$session->hashedToken()] = [
                'expires_at' => $session->expiresAt(),
                'last_activity' => $session->lastActivity(),
                'last_rotation' => $session->lastRotation(),
                'data' => $session->data(),
            ];
            $this->session_cache[$session->hashedToken()] = $session;
        }

        return $sessions;
    }

    public function destroyOtherSessionsForUser(int $user_id, string $hashed_token_to_keep): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('delete from `%s` where `user_id` = ? and `hashed_token` != ? ', $this->table_name);

        $this->db->preparedQuery(
            $sql,
            [$user_id, $hashed_token_to_keep]
        );

        $this->session_cache = [];
    }

    public function destroyAllSessionsForUser(int $user_id): void
    {
        $this->db->delete($this->table_name, [
            'user_id' => $user_id,
        ]);
        $this->session_cache = [];
    }

    public function destroyAll(): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('DELETE FROM `%s`', $this->table_name);

        $this->db->unprepared($sql);

        $this->session_cache = [];
    }

    public function updateActivity(string $hashed_token): void
    {
        if (! $this->exists($hashed_token)) {
            throw InvalidSessionToken::forToken($hashed_token);
        }

        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => $hashed_token,
            ],
            [
                'last_activity' => $this->clock->currentTimestamp(),
            ]
        );

        unset($this->session_cache[$hashed_token]);
    }

    public function rotateToken(string $hashed_token_old, string $hashed_token_new, int $current_timestamp): void
    {
        if (! $this->exists($hashed_token_old)) {
            throw InvalidSessionToken::forToken($hashed_token_old);
        }

        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => $hashed_token_old,
            ],
            [
                'last_rotation' => $current_timestamp,
                'hashed_token' => $hashed_token_new,
            ]
        );

        unset($this->session_cache[$hashed_token_old]);
    }

    public function gc(): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('delete from `%s` where `expires_at` < ?', $this->table_name);

        $this->db->preparedQuery(
            $sql,
            [$this->clock->currentTimestamp()]
        );

        $this->session_cache = [];
    }

    private function exists(string $hashed_token): bool
    {
        return $this->db->exists($this->table_name, [
            'hashed_token' => $hashed_token,
        ]);
    }

    private function encodePayload(array $session): string
    {
        return base64_encode(serialize($session));
    }

    private function decodePayload(string $payload): array
    {
        $decoded = @unserialize((string) base64_decode($payload, true));

        if (! is_array($decoded)) {
            throw new RuntimeException(
                sprintf(
                    "Session payload is corrupted.\nPayload value should be an array.\nGot: %s",
                    gettype($decoded)
                )
            );
        }

        return $decoded;
    }

    /**
     * @throws NoMatchingRowFound
     */
    private function querySession(string $hashed_token): AuthSession
    {
        /** @var array{payload:string, last_activity: int, last_rotation: int, user_id: int, expires_at: int} $row */
        $row = $this->db->selectRow(
            "select `payload`, `last_activity`, `last_rotation`, `user_id`, `expires_at`
                     from `{$this->table_name}`
                     where `expires_at` >= ?
                     and `hashed_token` = ?
                     ",
            [$this->clock->currentTimestamp(), $hashed_token]
        );

        $data = $this->decodePayload($row['payload']);

        return new AuthSession(
            $hashed_token,
            $row['user_id'],
            $row['last_activity'],
            $row['last_rotation'],
            $data,
        );
    }

    private function isExpired(AuthSession $session): bool
    {
        return $session->expiresAt() < $this->clock->currentTimestamp();
    }
}
