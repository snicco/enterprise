<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth;

use InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Event\SessionRotationIntervalExceeded;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasRotated;

use function array_map;
use function base64_decode;
use function base64_encode;
use function bin2hex;
use function gettype;
use function hash;
use function is_array;
use function is_int;
use function is_string;
use function random_bytes;
use function serialize;
use function sprintf;
use function time;
use function unserialize;

/**
 * @internal
 *
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 */
final class SessionRepository
{
    private EventDispatcher $event_dispatcher;

    private BetterWPDB      $db;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    private int    $idle_timeout;

    private int    $rotation_interval;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(
        EventDispatcher $event_dispatcher,
        BetterWPDB $db,
        string $table_name,
        int $idle_timeout,
        int $rotation_interval
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->db = $db;
        $this->table_name = $table_name;
        $this->idle_timeout = $idle_timeout;
        $this->rotation_interval = $rotation_interval;
    }

    public function createTable(): void
    {
        $this->db->unprepared(
            "CREATE TABLE IF NOT EXISTS `{$this->table_name}`  (
            	`id` INTEGER(11) NOT NULL AUTO_INCREMENT,
                `hashed_token` CHAR(64) NOT NULL UNIQUE,
                `user_id` INTEGER(11) unsigned NOT NULL,
                `payload` TEXT NOT NULL,
                `created_at` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `expires_at` INTEGER(11) UNSIGNED NOT NULL,
                `last_activity` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `next_rotation_at` INTEGER(11) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                KEY (`hashed_token`),
                KEY (`user_id`),
                KEY (`expires_at`)
        );"
        );
    }

    /**
     * @return array[]
     */
    public function getSessions(int $user_id): array
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('select `payload` from %s where `user_id` = ? and `expires_at` >= ?', $this->table_name);

        /** @var array<array{payload: string}> $sessions */
        $sessions = $this->db->selectAll($sql, [$user_id, time()]);

        return array_map(fn (array $row): array => $this->decodePayload($row['payload']), $sessions);
    }

    public function getSession(int $user_id, string $hashed_token): ?array
    {
        $now = time();

        try {
            /** @var array{payload:string, last_activity: int, next_rotation_at: int} $row */
            $row = $this->db->selectRow(
                "select `payload`, `last_activity`, `next_rotation_at`
                     from {$this->table_name}
                     where `user_id` = ?
                     and `expires_at` >= ?
                     and `hashed_token` = ?
                     ",
                [$user_id, $now, $hashed_token]
            );
        } catch (NoMatchingRowFound $e) {
            return null;
        }

        $seconds_without_activity = $now - $row['last_activity'];

        if ($seconds_without_activity > $this->idle_timeout) {
            $this->event_dispatcher->dispatch(
                new SessionWasIdle($hashed_token, $user_id)
            );

            return null;
        }

        if ($row['next_rotation_at'] <= $now) {
            $this->event_dispatcher->dispatch(
                new SessionRotationIntervalExceeded($hashed_token, $user_id)
            );
        }

        return $this->decodePayload($row['payload']);
    }

    public function update(int $user_id, string $hashed_token, array $session): void
    {
        if ($this->exists($hashed_token)) {
            $payload = $this->encodePayload($session);

            $this->db->update($this->table_name, [
                'hashed_token' => $hashed_token,
            ], [
                'payload' => $payload,
            ]);

            return;
        }

        if (! isset($session['expiration']) || ! is_int($session['expiration'])) {
            throw new InvalidArgumentException('The session array must contain an expiration timestamp.');
        }

        $payload = $this->encodePayload($session);

        $this->db->insert($this->table_name, [
            'hashed_token' => $hashed_token,
            'user_id' => $user_id,
            'payload' => $payload,
            'expires_at' => $session['expiration'],
            'next_rotation_at' => time() + $this->rotation_interval,
        ]);
    }

    public function delete(string $hashed_token): void
    {
        $this->db->delete($this->table_name, [
            'hashed_token' => $hashed_token,
        ]);
    }

    public function destroyOtherSessionsForUser(int $user_id, string $hashed_token): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('delete from `%s` where `user_id` = ? and `hashed_token` != ? ', $this->table_name);

        $this->db->preparedQuery(
            $sql,
            [$user_id, $hashed_token]
        );
    }

    public function destroyAllSessionsForUser(int $user_id): void
    {
        $this->db->delete($this->table_name, [
            'user_id' => $user_id,
        ]);
    }

    public function destroyAllSessions(): void
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('DELETE FROM `%s`', $this->table_name);

        $this->db->unprepared($sql);
    }

    public function touch(string $hashed_token): void
    {
        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => $hashed_token,
            ],
            [
                'last_activity' => time(),
            ]
        );
    }

    public function rotate(string $hashed_token): void
    {
        $new_token = bin2hex(random_bytes(32));

        $new_token_hashed = hash('sha256', $new_token);

        // @codeCoverageIgnoreStart
        if (! is_string($new_token_hashed)) {
            throw new RuntimeException('Could not hash new session token');
        }

        // @codeCoverageIgnoreEnd

        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => $hashed_token,
            ],
            [
                'next_rotation_at' => time() + 10,
                'hashed_token' => $new_token_hashed,
            ]
        );

        $this->event_dispatcher->dispatch(new SessionWasRotated($new_token, $new_token_hashed));
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
}
