<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\TwoFactor;

use LogicException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;

use function base64_decode;
use function base64_encode;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/** @psalm-suppress ArgumentTypeCoercion */
final class TwoFactorSettingsBetterWPDB implements TwoFactorOTPSettings
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(BetterWPDB $db, string $table_name)
    {
        $this->db = $db;
        $this->table_name = $table_name;
    }

    public function createTable(): void
    {
        $this->db->unprepared(
            'CREATE TABLE IF NOT EXISTS `two_factor_settings` (

	`id` integer(11) NOT NULL AUTO_INCREMENT,
	`user_id` integer(11) unsigned NOT NULL UNIQUE,
    `completed` tinyint DEFAULT 0,
    `pending` tinyint DEFAULT 1,
    `secret` varchar(360),
    `last_used` datetime DEFAULT NULL,
    `backup_codes` text,
	PRIMARY KEY (`id`)

);'
        );
    }

    public function isSetupCompleteForUser(int $user_id): bool
    {
        try {
            return (bool) $this->db->selectValue(
                sprintf('select `completed` from `%s` where `user_id` = ?', $this->table_name),
                [$user_id]
            );
        } catch (NoMatchingRowFound $e) {
            return false;
        }
    }

    public function isSetupPendingForUser(int $user_id): bool
    {
        try {
            return (bool) $this->db->selectValue(
                sprintf('select `pending` from `%s` where `user_id` = ?', $this->table_name),
                [$user_id]
            );
        } catch (NoMatchingRowFound $e) {
            return false;
        }
    }

    public function initiateSetup(int $user_id, string $secret_key, BackupCodes $backup_codes): void
    {
        if ($this->exists($user_id)) {
            throw new LogicException(sprintf('Two factor settings cant be initiated twice for user_id %d', $user_id));
        }

        $this->db->insert($this->table_name, [
            'secret' => $secret_key,
            'user_id' => $user_id,
            'backup_codes' => $this->encodeBackupCodes($backup_codes),
        ]);
    }

    public function completeSetup(int $user_id): void
    {
        if (! $this->exists($user_id)) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $this->db->update($this->table_name, [
            'user_id' => $user_id,
        ], [
            'completed' => true,
            'pending' => false,
        ]);
    }

    public function getSecretKey(int $user_id): string
    {
        try {
            return (string) $this->db->selectValue(
                sprintf('select `secret` from `%s` where `user_id` = ?', $this->table_name),
                [$user_id]
            );
        } catch (NoMatchingRowFound $e) {
            throw No2FaSettingsFound::forUser($user_id);
        }
    }

    public function lastUsedTimestamp(int $user_id): ?int
    {
        try {
            /** @var int|null $value */
            $value = $this->db->selectValue(
                sprintf('select `last_used` from `%s` where `user_id` = ?', $this->table_name),
                [$user_id]
            );

            return $value ?? null;
        } catch (NoMatchingRowFound $e) {
            throw No2FaSettingsFound::forUser($user_id);
        }
    }

    public function updateLastUseTimestamp(int $user_id, int $timestamp): void
    {
        if (! $this->exists($user_id)) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $this->db->update($this->table_name, [
            'user_id' => $user_id,
        ], [
            'last_used' => $timestamp,
        ]);
    }

    public function delete(int $user_id): void
    {
        $this->db->delete($this->table_name, [
            'user_id' => $user_id,
        ]);
    }

    public function getBackupCodes(int $user_id): BackupCodes
    {
        if (! $this->exists($user_id)) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $codes = (string) $this->db->selectValue(
            sprintf('select `backup_codes` from `%s` where `user_id` = ?', $this->table_name),
            [$user_id]
        );

        return $this->decodeBackupCodes($codes);
    }

    public function updateBackupCodes(int $user_id, BackupCodes $backup_codes): void
    {
        if (! $this->exists($user_id)) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $this->db->update(
            $this->table_name,
            [
                'user_id' => $user_id,
            ],
            [
                'backup_codes' => $this->encodeBackupCodes($backup_codes),
            ]
        );
    }

    private function exists(int $user_id): bool
    {
        return $this->db->exists($this->table_name, [
            'user_id' => $user_id,
        ]);
    }

    private function encodeBackupCodes(BackupCodes $backup_codes): string
    {
        return base64_encode(json_encode(iterator_to_array($backup_codes), JSON_THROW_ON_ERROR));
    }

    private function decodeBackupCodes(string $codes): BackupCodes
    {
        /** @var string[] $decoded_codes */
        $decoded_codes = (array) json_decode((string) base64_decode($codes, true), false, 512, JSON_THROW_ON_ERROR);

        return BackupCodes::fromHashedCodes($decoded_codes);
    }
}
