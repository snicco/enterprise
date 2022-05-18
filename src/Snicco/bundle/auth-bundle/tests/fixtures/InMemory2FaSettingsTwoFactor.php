<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\fixtures;

use LogicException;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\BackupCodes;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\No2FaSettingsFound;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\TwoFactorOTPSettings;
use function array_map;
use function sprintf;

final class InMemory2FaSettingsTwoFactor implements TwoFactorOTPSettings
{
    /**
     * @var array<int,array{is_complete: bool, is_pending: bool, secret_key: string, backup_codes: BackupCodes, last_used?: ?int }>
     */
    private array $user_settings;

    /**
     * @param array<int,array{secret:string, last_used?:int}> $user_settings
     *
     * @return array{is_complete: true, is_pending: true, secret_key: string, last_used: int|null, backup_codes: BackupCodes}
     */
    public function __construct(array $user_settings)
    {
        $this->user_settings = array_map(fn (array $settings): array => [
            'is_complete' => true,
            'is_pending' => false,
            'secret_key' => $settings['secret'],
            'last_used' => $settings['last_used'] ?? null,
            'backup_codes' => BackupCodes::fromPlainCodes(),
        ], $user_settings);
    }

    public function isSetupCompleteForUser(int $user_id): bool
    {
        if (! isset($this->user_settings[$user_id])) {
            return false;
        }

        $current = $this->user_settings[$user_id];

        return $current['is_complete'];
    }

    public function isSetupPendingForUser(int $user_id): bool
    {
        if (! isset($this->user_settings[$user_id])) {
            return false;
        }

        return $this->user_settings[$user_id]['is_pending'];
    }

    public function initiateSetup(int $user_id, string $secret_key, BackupCodes $backup_codes): void
    {
        if (isset($this->user_settings[$user_id])) {
            throw new LogicException(sprintf('Cant initiate twice for user_id [%d]', $user_id));
        }

        $new = [
            'secret_key' => $secret_key,
            'is_pending' => true,
            'is_complete' => false,
            'backup_codes' => $backup_codes,
        ];

        $this->user_settings[$user_id] = $new;
    }

    public function completeSetup(int $user_id): void
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $current = $this->user_settings[$user_id];

        $current['is_pending'] = false;
        $current['is_complete'] = true;
        $this->user_settings[$user_id] = $current;
    }

    public function getSecretKey(int $user_id): string
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        return $this->user_settings[$user_id]['secret_key'];
    }

    public function lastUsedTimestamp(int $user_id): ?int
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        return $this->user_settings[$user_id]['last_used'] ?? null;
    }

    public function updateLastUseTimestamp(int $user_id, int $timestamp): void
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $this->user_settings[$user_id]['last_used'] = $timestamp;
    }

    public function delete(int $user_id): void
    {
        unset($this->user_settings[$user_id]);
    }

    public function getBackupCodes(int $user_id): BackupCodes
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        return $this->user_settings[$user_id]['backup_codes'];
    }

    public function updateBackupCodes(int $user_id, BackupCodes $backup_codes): void
    {
        if (! isset($this->user_settings[$user_id])) {
            throw No2FaSettingsFound::forUser($user_id);
        }

        $this->user_settings[$user_id]['backup_codes'] = $backup_codes;
    }
}
