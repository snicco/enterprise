<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authentication\TwoFactor;

use Codeception\TestCase\WPTestCase;
use Defuse\Crypto\Key;
use Generator;
use LogicException;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\BackupCodes;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\EncryptedTwoFactorSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\No2FaSettingsFound;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\TwoFactorOTPSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\TwoFactorSettingsBetterWPDB;

use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemory2FaSettingsTwoFactor;

use function time;

/**
 * @internal
 */
final class OTPTTwoFactorSettingsTest extends WPTestCase
{
    /**
     * @var string
     */
    private const TABLE = 'two_factor_settings';

    protected function setUp(): void
    {
        parent::setUp();
        $db_setup = new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), self::TABLE);
        $db_setup->createTable();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS ' . self::TABLE);
    }

    /**
     * @test
     * @dataProvider twoFactorSetups
     */
    public function that_a_everything_is_false_by_default(TwoFactorOTPSettings $two_factor_setup): void
    {
        $default_user_id = 1;

        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_setup->isSetupCompleteForUser($default_user_id));
    }

    /**
     * @test
     *
     * @dataProvider twoFactorSetups
     */
    public function that_exceptions_are_thrown_for_accessing_settings_for_missing_users(
        TwoFactorOTPSettings $two_factor_setup
    ): void {
        $default_user_id = 1;

        try {
            $two_factor_setup->getSecretKey($default_user_id);

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }

        try {
            $two_factor_setup->lastUsedTimestamp($default_user_id);

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }

        try {
            $two_factor_setup->updateLastUseTimestamp($default_user_id, time());

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }

        try {
            $two_factor_setup->completeSetup($default_user_id);

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }

        try {
            $two_factor_setup->updateBackupCodes($default_user_id, BackupCodes::fromPlainCodes());

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }

        try {
            $two_factor_setup->getBackupCodes($default_user_id);

            throw new RuntimeException('Should have thrown exception');
        } catch (No2FaSettingsFound $e) {
            $this->assertStringContainsString('No 2FA', $e->getMessage());
        }
    }

    /**
     * @test
     *
     * @dataProvider twoFactorSetups
     */
    public function that_settings_can_be_initiated_completed_and_deleted(TwoFactorOTPSettings $two_factor_setup): void
    {
        $default_user_id = 1;

        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));

        $codes = BackupCodes::fromPlainCodes(BackupCodes::generate());
        $two_factor_setup->initiateSetup($default_user_id, 'secret', $codes);

        $this->assertTrue($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));
        $this->assertEquals($codes, $two_factor_setup->getBackupCodes($default_user_id));

        try {
            $two_factor_setup->initiateSetup($default_user_id, 'secret2', BackupCodes::fromPlainCodes());

            throw new RuntimeException('Should not be able to initiate twice');
        } catch (LogicException $e) {
        }

        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));

        $two_factor_setup->completeSetup($default_user_id, );
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertTrue($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));

        $two_factor_setup->completeSetup($default_user_id);
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertTrue($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));

        $two_factor_setup->delete($default_user_id);
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_setup->isSetupCompleteForUser($default_user_id));
    }

    /**
     * @test
     *
     * @dataProvider twoFactorSetups
     */
    public function that_backup_codes_can_be_updated(TwoFactorOTPSettings $settings): void
    {
        $settings->initiateSetup(1, 'secret', $initial = BackupCodes::fromPlainCodes());

        $this->assertEquals($initial, $settings->getBackupCodes(1));

        $settings->updateBackupCodes(1, $new = BackupCodes::fromPlainCodes());

        $this->assertEquals($new, $settings->getBackupCodes(1));
        $this->assertNotEquals($initial, $settings->getBackupCodes(1));
    }

    /**
     * @test
     *
     * @dataProvider nonEncryptedSetups
     */
    public function that_secrets_are_encrypted(TwoFactorOTPSettings $two_factor_setup): void
    {
        $google_2fa = new Google2FA();
        $secret = $google_2fa->generateSecretKey();

        $encrypted_two_factor_setup = new EncryptedTwoFactorSettings(
            new DefuseEncryptor(Key::loadFromAsciiSafeString(DefuseEncryptor::randomAsciiKey())),
            $two_factor_setup
        );

        $encrypted_two_factor_setup->initiateSetup(1, $secret, BackupCodes::fromPlainCodes());

        $this->assertNotSame($secret, $two_factor_setup->getSecretKey(1));
        $this->assertSame($secret, $encrypted_two_factor_setup->getSecretKey(1));
    }

    public function twoFactorSetups(): Generator
    {
        yield [new InMemory2FaSettingsTwoFactor([])];

        yield [
            new EncryptedTwoFactorSettings(
                new DefuseEncryptor(Key::loadFromAsciiSafeString(DefuseEncryptor::randomAsciiKey())),
                new InMemory2FaSettingsTwoFactor([])
            ),
        ];

        $setup = new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), self::TABLE);
        $setup->createTable();

        yield [$setup];
    }

    public function nonEncryptedSetups(): Generator
    {
        yield [new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), self::TABLE)];

        yield [new InMemory2FaSettingsTwoFactor([])];
    }
}
