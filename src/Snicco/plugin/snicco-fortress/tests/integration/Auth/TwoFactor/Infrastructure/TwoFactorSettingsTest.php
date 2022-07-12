<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\integration\Auth\TwoFactor\Infrastructure;

use Closure;
use Codeception\TestCase\WPTestCase;
use Defuse\Crypto\Key;
use Generator;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\No2FaSettingsFound;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupAlreadyCompleted;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsAlreadyInitialized;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsNotInitialized;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\EncryptedTwoFactorSettings;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;

use function time;

/**
 * @internal
 */
final class TwoFactorSettingsTest extends WPTestCase
{
    /**
     * @var string
     */
    private const TABLE = 'two_factor_settings';

    protected function setUp(): void
    {
        TwoFactorSettingsBetterWPDB::createTable(BetterWPDB::fromWpdb(), self::TABLE);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS ' . self::TABLE);
        parent::tearDown();
    }

    /**
     * For some reason mysql completely hangs if the "database" instance is not
     * returned as a closure here which makes no sense. This also only happens
     * when using PHPUnit dataGenerators.
     */
    public function twoFactorSetups(): Generator
    {
        yield 'in-memory' => [
            fn (): InMemoryTwoFactorSettings => new InMemoryTwoFactorSettings([]),
        ];

        yield 'encrypted-in-memory' => [
            fn (): EncryptedTwoFactorSettings => new EncryptedTwoFactorSettings(
                new DefuseEncryptor(Key::loadFromAsciiSafeString(DefuseEncryptor::randomAsciiKey())),
                new InMemoryTwoFactorSettings([])
            ),
        ];

        yield 'database' => [
            fn (): TwoFactorSettingsBetterWPDB => new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), self::TABLE),
        ];
    }

    public function nonEncryptedSetups(): Generator
    {
        yield 'database' => [
            fn (): TwoFactorSettingsBetterWPDB => new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), self::TABLE),
        ];

        yield 'memory' => [
            fn (): InMemoryTwoFactorSettings => new InMemoryTwoFactorSettings([]),
        ];
    }

    /**
     * @test
     * @dataProvider twoFactorSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_settings
     */
    public function that_a_everything_is_false_by_default(Closure $two_factor_settings): void
    {
        $default_user_id = 1;

        $this->assertFalse($two_factor_settings()->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_settings()->isSetupCompleteForUser($default_user_id));
    }

    /**
     * @test
     * @dataProvider twoFactorSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_setup
     */
    public function that_exceptions_are_thrown_for_accessing_settings_for_missing_users(Closure $two_factor_setup): void
    {
        $default_user_id = 1;

        $two_factor_setup = $two_factor_setup();

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
     * @dataProvider twoFactorSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_setup
     */
    public function that_settings_can_be_initiated_completed_and_deleted(Closure $two_factor_setup): void
    {
        $default_user_id = 1;
        $two_factor_setup = $two_factor_setup();
        $codes = BackupCodes::fromPlainCodes(BackupCodes::generate());

        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));

        try {
            $two_factor_setup->completeSetup($default_user_id);

            throw new RuntimeException('Should not be able to complete before initializing');
        } catch (TwoFactorSetupIsNotInitialized $e) {
        }

        $two_factor_setup->initiateSetup($default_user_id, 'secret', $codes);

        $this->assertTrue($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));
        $this->assertEquals($codes, $two_factor_setup->getBackupCodes($default_user_id));

        try {
            $two_factor_setup->initiateSetup($default_user_id, 'secret', $codes);

            throw new RuntimeException('Should not be able to initiate setup twice.');
        } catch (TwoFactorSetupIsAlreadyInitialized $e) {
        }

        $two_factor_setup->completeSetup($default_user_id, );
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertTrue($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));

        // Does nothing.
        $two_factor_setup->completeSetup($default_user_id);
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertTrue($two_factor_setup->isSetupCompleteForUser($default_user_id));
        $this->assertSame('secret', $two_factor_setup->getSecretKey($default_user_id));

        try {
            $two_factor_setup->initiateSetup($default_user_id, 'secret', $codes);

            throw new RuntimeException('Should not be able to initiate on completed settings');
        } catch (TwoFactorSetupAlreadyCompleted $e) {
        }

        $two_factor_setup->delete($default_user_id);
        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
        $this->assertFalse($two_factor_setup->isSetupCompleteForUser($default_user_id));

        try {
            $two_factor_setup->delete($default_user_id);

            throw new RuntimeException('Should not be able to delete twice');
        } catch (No2FaSettingsFound $e) {
        }
    }

    /**
     * @test
     * @dataProvider twoFactorSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_setup
     */
    public function that_the_last_used_timestamp_can_be_retrieved_and_updated(Closure $two_factor_setup): void
    {
        $default_user_id = 1;

        $two_factor_setup = $two_factor_setup();

        $this->assertFalse($two_factor_setup->isSetupPendingForUser($default_user_id));
    }

    /**
     * @test
     * @dataProvider twoFactorSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_setup
     */
    public function that_backup_codes_can_be_updated(Closure $two_factor_setup): void
    {
        $two_factor_setup = $two_factor_setup();

        $two_factor_setup->initiateSetup(1, 'secret', $initial = BackupCodes::fromPlainCodes());

        $this->assertEquals($initial, $two_factor_setup->getBackupCodes(1));

        $two_factor_setup->updateBackupCodes(1, $new = BackupCodes::fromPlainCodes());

        $this->assertEquals($new, $two_factor_setup->getBackupCodes(1));
        $this->assertNotEquals($initial, $two_factor_setup->getBackupCodes(1));
    }

    /**
     * @test
     *
     * @dataProvider nonEncryptedSetups
     *
     * @param Closure():TwoFactorSettings $two_factor_setup
     */
    public function that_secrets_are_encrypted(Closure $two_factor_setup): void
    {
        $two_factor_setup = $two_factor_setup();

        $google_2fa = new Google2FA();

        /** @var non-empty-string $secret */
        $secret = $google_2fa->generateSecretKey();

        $encrypted_two_factor_setup = new EncryptedTwoFactorSettings(
            new DefuseEncryptor(Key::loadFromAsciiSafeString(DefuseEncryptor::randomAsciiKey())),
            $two_factor_setup
        );

        $encrypted_two_factor_setup->initiateSetup(1, $secret, BackupCodes::fromPlainCodes());

        $this->assertNotSame($secret, $two_factor_setup->getSecretKey(1));
        $this->assertSame($secret, $encrypted_two_factor_setup->getSecretKey(1));
    }
}
