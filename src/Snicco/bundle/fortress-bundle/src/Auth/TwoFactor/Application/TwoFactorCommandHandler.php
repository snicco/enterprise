<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application;

use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Delete2Fa\Delete2FaSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\ResetBackupCodes\ResetBackupCodes;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupAlreadyCompleted;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsNotInitialized;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserNotFound;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserProvider;
use function sprintf;

final class TwoFactorCommandHandler
{
    private TwoFactorSettings $two_factor_settings;

    private OTPValidator $validator;

    private UserProvider $user_provider;

    public function __construct(
        TwoFactorSettings $two_factor_settings,
        UserProvider $user_provider,
        OTPValidator $validator
    ) {
        $this->two_factor_settings = $two_factor_settings;
        $this->validator = $validator;
        $this->user_provider = $user_provider;
    }

    public function initialize2Fa(Initialize2Fa $command): void
    {
        $user = $command->user_id;

        $this->guardAgainstMissingUserId($user);

        $this->two_factor_settings->initiateSetup(
            $user,
            $command->secret_key_plain_text,
            BackupCodes::fromPlainCodes($command->backup_codes)
        );
    }

    public function complete2FaSetup(Complete2FaSetup $command): void
    {
        $user_id = $command->user_id;

        $this->guardAgainstMissingUserId($user_id);

        if ($this->two_factor_settings->isSetupCompleteForUser($user_id)) {
            throw TwoFactorSetupAlreadyCompleted::forUser($user_id);
        }

        if (! $this->two_factor_settings->isSetupPendingForUser($user_id)) {
            throw TwoFactorSetupIsNotInitialized::forUser($user_id);
        }

        $otp_code = $command->otp_code;

        $this->validator->validate($otp_code, $user_id);

        $this->two_factor_settings->completeSetup($command->user_id);
    }

    public function delete2Fa(Delete2FaSettings $command): void
    {
        $this->guardAgainstMissingUserId($command->user_id);

        $this->two_factor_settings->delete($command->user_id);
    }

    public function resetBackupCodes(ResetBackupCodes $command): void
    {
        $user_id = $command->user_id;

        $this->guardAgainstMissingUserId($user_id);

        $this->two_factor_settings->updateBackupCodes(
            $user_id,
            BackupCodes::fromPlainCodes($command->new_codes)
        );
    }

    private function guardAgainstMissingUserId(int $user_id): void
    {
        if (! $this->user_provider->exists((string) $user_id)) {
            throw new UserNotFound(sprintf('No user with the identifier [%d] exists.', $user_id));
        }
    }
}
