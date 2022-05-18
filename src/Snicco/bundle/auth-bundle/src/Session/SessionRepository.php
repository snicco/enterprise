<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Session;

use RuntimeException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Session\Event\SessionWasIdle;
use Snicco\Enterprise\Bundle\Auth\Session\Event\SessionWasRotated;

use function array_map;
use function array_values;
use function base64_decode;
use function base64_encode;
use function bin2hex;
use function gettype;
use function hash;
use function is_array;
use function is_string;
use function random_bytes;
use function serialize;
use function sprintf;
use function time;
use function unserialize;

/**
 * @internal
 * @psalm-internal Snicco\Enterprise\Bundle\Auth
 */
final class SessionRepository
{
    private EventDispatcher $event_dispatcher;

    private BetterWPDB $db;

    private TimeoutResolver $timeout_resolver;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    /**
     * @var array<string,string>
     */
    private array $rotated_sessions_ids = [];

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(
        EventDispatcher $event_dispatcher,
        BetterWPDB $db,
        TimeoutResolver $timeout_resolver,
        string $table_name
    ) {
        $this->event_dispatcher = $event_dispatcher;
        $this->db = $db;
        $this->table_name = $table_name;
        $this->timeout_resolver = $timeout_resolver;
    }

    public static function createTable(string $table_name): void
    {
        BetterWPDB::fromWpdb()->unprepared(
            "CREATE TABLE IF NOT EXISTS `{$table_name}`  (
            	`id` INTEGER(11) NOT NULL AUTO_INCREMENT,
                `hashed_token` CHAR(64) NOT NULL,
                `user_id` INTEGER(11) unsigned NOT NULL,
                `payload` TEXT NOT NULL,
                `expires_at` INTEGER(11) UNSIGNED NOT NULL,
                `last_activity` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `last_rotation` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                `created_at` INTEGER(11) UNSIGNED NOT NULL DEFAULT (UNIX_TIMESTAMP()),
                PRIMARY KEY (`id`),
                UNIQUE KEY (`hashed_token`),
                KEY (`user_id`),
                KEY (`expires_at`)
        );"
        );
    }

    /**
     * @return list<array>
     */
    public function getSessions(int $user_id): array
    {
        /** @var non-empty-string $sql */
        $sql = sprintf('select `payload` from %s where `user_id` = ? and `expires_at` >= ?', $this->table_name);

        /** @var array<array{payload: string}> $sessions */
        $sessions = $this->db->selectAll($sql, [$user_id, time()]);

        return array_values(
            array_map(
                fn (array $row): array => $this->decodePayload($row['payload']),
                $sessions
            )
        );
    }

    public function getSession(string $hashed_token): AuthSession
    {
        $hashed_token = $this->rotated_sessions_ids[$hashed_token] ?? $hashed_token;

        try {
            /** @var array{payload:string, last_activity: int, last_rotation: int, user_id: int, expires_at: int} $row */
            $row = $this->db->selectRow(
                "select `payload`, `last_activity`, `last_rotation`, `user_id`, `expires_at`
                     from `{$this->table_name}`
                     where `expires_at` >= ?
                     and `hashed_token` = ?
                     ",
                [time(), $hashed_token]
            );
        } catch (NoMatchingRowFound $e) {
            throw new InvalidSessionToken($hashed_token);
        }

        $user_id = $row['user_id'];

        $idle_status = $this->timeout_resolver->idleStatus($row['last_activity'], $user_id);
        $is_idle = TimeoutResolver::TOTALLY_IDLE === $idle_status;

        if ($is_idle) {
            $this->delete($hashed_token);

            $this->event_dispatcher->dispatch(
                new SessionWasIdle($hashed_token, $user_id)
            );

            throw new InvalidSessionToken($hashed_token);
        }

        $fully_authenticated = TimeoutResolver::NOT_IDLE === $idle_status;

        if ($this->timeout_resolver->needsRotation($row['last_rotation'], $user_id)) {
            $hashed_token = $this->rotate($hashed_token, $user_id);
        }

        $data = $this->decodePayload($row['payload']);

        $data['__snicco_fully_authenticated'] = $fully_authenticated;

        return new AuthSession(
            $hashed_token,
            $user_id,
            $data,
        );
    }

    public function save(AuthSession $session): void
    {
        $data = $session->data();
        $token = $session->hashedToken();

        $hashed_token = $this->rotated_sessions_ids[$token] ?? $token;

        $payload = $this->encodePayload($data);

        if ($this->exists($hashed_token)) {
            $this->db->update($this->table_name, [
                'hashed_token' => $hashed_token,
            ], [
                'payload' => $payload,
            ]);

            return;
        }

        $this->db->insert($this->table_name, [
            'hashed_token' => $hashed_token,
            'user_id' => $session->userId(),
            'payload' => $payload,
            'expires_at' => $session->expiresAt(),
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

    public function destroyAllSessionsForAllUsers(): void
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

    public function rotate(string $hashed_token, int $user_id): string
    {
        $new_token = bin2hex(random_bytes(32));

        $new_token_hashed = $this->hashToken($new_token);

        $this->db->update(
            $this->table_name,
            [
                'hashed_token' => $hashed_token,
            ],
            [
                'last_rotation' => time(),
                'hashed_token' => $new_token_hashed,
            ]
        );

        $this->rotated_sessions_ids[$hashed_token] = $new_token_hashed;

        $this->event_dispatcher->dispatch(new SessionWasRotated($user_id, $new_token, $new_token_hashed));

        return $new_token_hashed;
    }

    public function hashToken(string $raw_token): string
    {
        // The hash algo must always be the same as in WP_Session_Tokens
        $token_hashed = hash('sha256', $raw_token);

        // @codeCoverageIgnoreStart
        if (! is_string($token_hashed)) {
            throw new RuntimeException('Could not hash new session token');
        }

        return $token_hashed;
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
