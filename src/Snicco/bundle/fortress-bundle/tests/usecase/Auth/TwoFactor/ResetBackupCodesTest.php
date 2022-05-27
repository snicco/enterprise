<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\usecase\Auth\TwoFactor;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\ResetBackupCodes\ResetBackupCodes;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\Exception\No2FaSettingsFound;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\MD5OTPValidator;

/**
 * @internal
 */
final class ResetBackupCodesTest extends Unit
{
    private TwoFactorCommandHandler $handler;

    private InMemoryTwoFactorSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new InMemoryTwoFactorSettings();
        $this->handler = new TwoFactorCommandHandler(
            $this->settings,
            new MD5OTPValidator($this->settings)
        );
    }

    /**
     * @test
     */
    public function that_backup_codes_cant_be_reset_for_a_user_without_completed_2fa(): void
    {
        $this->expectException(No2FaSettingsFound::class);

        $this->handler->resetBackupCodes(new ResetBackupCodes(1, BackupCodes::generate()));
    }

    /**
     * @test
     */
    public function that_backup_codes_can_be_updated(): void
    {
        $this->settings->add(1, [
            'secret' => 'secret',
        ]);

        $old_codes = $this->settings->getBackupCodes(1);

        $new_codes_plain = BackupCodes::generate();

        $this->handler->resetBackupCodes(new ResetBackupCodes(1, $new_codes_plain));

        $this->assertNotEquals($old_codes, $stored_codes = $this->settings->getBackupCodes(1));

        $stored_codes->revoke($new_codes_plain[0]);
    }
}
