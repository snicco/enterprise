<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain;

use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\Exception\No2FaSettingsFound;

interface TwoFactorSettings
{
    public function isSetupCompleteForUser(int $user_id): bool;

    public function isSetupPendingForUser(int $user_id): bool;
    
    /**
     * @param non-empty-string $secret_key
     *
     * @throws TwoFactorSetupIsAlreadyInitialized
     * @throws TwoFactorSetupAlreadyCompleted
     */
    public function initiateSetup(int $user_id, string $secret_key, BackupCodes $backup_codes): void;

    /**
     * @throws TwoFactorSetupIsNotInitialized
     */
    public function completeSetup(int $user_id): void;
    
    /**
     * @throws No2FaSettingsFound
     */
    public function delete(int $user_id): void;
    
    /**
     * @return non-empty-string
     *
     * @throws No2FaSettingsFound
     */
    public function getSecretKey(int $user_id): string;

    /**
     * @throws No2FaSettingsFound
     */
    public function getBackupCodes(int $user_id): BackupCodes;

    /**
     * @throws No2FaSettingsFound
     */
    public function lastUsedTimestamp(int $user_id): ?int;

    /**
     * @throws No2FaSettingsFound
     */
    public function updateLastUseTimestamp(int $user_id, int $timestamp): void;

    /**
     * @throws No2FaSettingsFound
     */
    public function updateBackupCodes(int $user_id, BackupCodes $backup_codes): void;
}
