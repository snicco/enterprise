<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\fixtures;

use LogicException;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\No2FaSettingsFound;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsNotInitialized;

use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\TwoFactorSetupAlreadyCompleted;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsAlreadyInitialized;

use function sprintf;

final class InMemoryTwoFactorSettings implements TwoFactorSettings
{
    /**
     * @var array<int,array{is_complete: bool, is_pending: bool, secret_key: string, backup_codes: BackupCodes, last_used?: ?int }>
     */
    private array $user_settings = [];

    /**
     * @param array<positive-int,array{secret:string, last_used?:int, complete?: bool}> $user_settings
     */
    public function __construct(array $user_settings = [])
    {
        foreach ($user_settings as $id => $settings) {
            $this->add($id, $settings);
        }
    }

    /**
     * @param positive-int                         $user_id
     * @param array{secret:string, last_used?:int, complete?: bool} $settings
     */
    public function add(int $user_id, array $settings): void
    {
        $is_complete = $settings['complete'] ?? true;
        
        $this->user_settings[$user_id] = [
            'is_complete' => $is_complete,
            'is_pending' => !$is_complete,
            'secret_key' => $settings['secret'],
            'last_used' => $settings['last_used'] ?? null,
            'backup_codes' => BackupCodes::fromPlainCodes(),
        ];
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
        $current = $this->user_settings[$user_id] ?? null;
        
        if(null === $current) {
            $this->user_settings[$user_id] = [
                'secret_key' => $secret_key,
                'is_pending' => true,
                'is_complete' => false,
                'backup_codes' => $backup_codes,
            ];
            return;
        }
        
        if($current['is_pending']) {
            throw TwoFactorSetupIsAlreadyInitialized::forUser($user_id);
    
        }
        
        throw TwoFactorSetupAlreadyCompleted::forUser($user_id);
    }

    public function completeSetup(int $user_id): void
    {
        if (! isset($this->user_settings[$user_id])) {
            throw TwoFactorSetupIsNotInitialized::forUser($user_id);
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
        if(!isset($this->user_settings[$user_id])){
            throw new No2FaSettingsFound();
        }
        
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
