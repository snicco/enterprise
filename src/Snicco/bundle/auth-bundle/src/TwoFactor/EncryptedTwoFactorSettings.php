<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\TwoFactor;

use Snicco\Bundle\Encryption\DefuseEncryptor;

final class EncryptedTwoFactorSettings implements TwoFactorOTPSettings
{
    private TwoFactorOTPSettings $two_factor_setup;

    private DefuseEncryptor      $defuse_encryptor;

    public function __construct(DefuseEncryptor $defuse_encryptor, TwoFactorOTPSettings $two_factor_setup)
    {
        $this->two_factor_setup = $two_factor_setup;
        $this->defuse_encryptor = $defuse_encryptor;
    }

    public function isSetupCompleteForUser(int $user_id): bool
    {
        return $this->two_factor_setup->isSetupCompleteForUser($user_id);
    }

    public function isSetupPendingForUser(int $user_id): bool
    {
        return $this->two_factor_setup->isSetupPendingForUser($user_id);
    }

    public function initiateSetup(int $user_id, string $secret_key, BackupCodes $backup_codes): void
    {
        $encrypted_secret_key = $this->defuse_encryptor->encrypt($secret_key);

        $this->two_factor_setup->initiateSetup($user_id, $encrypted_secret_key, $backup_codes);
    }

    public function completeSetup(int $user_id): void
    {
        $this->two_factor_setup->completeSetup($user_id);
    }

    public function getSecretKey(int $user_id): string
    {
        $encrypted_secret_key = $this->two_factor_setup->getSecretKey($user_id);

        return $this->defuse_encryptor->decrypt($encrypted_secret_key);
    }

    public function lastUsedTimestamp(int $user_id): ?int
    {
        return $this->two_factor_setup->lastUsedTimestamp($user_id);
    }

    public function updateLastUseTimestamp(int $user_id, int $timestamp): void
    {
        $this->two_factor_setup->updateLastUseTimestamp($user_id, $timestamp);
    }

    public function delete(int $user_id): void
    {
        $this->two_factor_setup->delete($user_id);
    }

    public function getBackupCodes(int $user_id): BackupCodes
    {
        return $this->two_factor_setup->getBackupCodes($user_id);
    }

    public function updateBackupCodes(int $user_id, BackupCodes $backup_codes): void
    {
        $this->two_factor_setup->updateBackupCodes($user_id, $backup_codes);
    }
}
