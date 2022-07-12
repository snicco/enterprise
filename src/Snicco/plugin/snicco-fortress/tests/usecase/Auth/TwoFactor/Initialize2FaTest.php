<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\usecase\Auth\TwoFactor;

use Codeception\Test\Unit;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\TwoFactorSetupAlreadyCompleted;
use Snicco\Enterprise\Fortress\Auth\User\Domain\UserNotFound;
use Snicco\Enterprise\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\Fortress\Tests\fixtures\MD5OTPValidator;
use Snicco\Enterprise\Fortress\Tests\fixtures\StubUserExistsProvider;

/**
 * @internal
 */
final class Initialize2FaTest extends Unit
{
    private TwoFactorCommandHandler $handler;

    private InMemoryTwoFactorSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new InMemoryTwoFactorSettings();
        $this->handler = new TwoFactorCommandHandler(
            $this->settings,
            new StubUserExistsProvider([1]),
            new MD5OTPValidator($this->settings)
        );
    }

    /**
     * @test
     */
    public function that_two_factor_settings_can_be_created_for_a_user(): void
    {
        $codes = BackupCodes::generate();

        $command = new Initialize2Fa(1, 'super-secret-key', $codes);

        $this->handler->initialize2Fa($command);

        $this->assertTrue($this->settings->isSetupPendingForUser(1));
        $this->assertFalse($this->settings->isSetupCompleteForUser(1));
        $this->assertSame('super-secret-key', $this->settings->getSecretKey(1));

        $stored_codes = $this->settings->getBackupCodes(1);

        $stored_codes->revoke($codes[0]);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_two_factor_settings_are_already_created(): void
    {
        $this->settings->add(1, [
            'secret' => 'foobar',
        ]);

        $command = new Initialize2Fa(1, 'foobaz', BackupCodes::generate());

        $this->expectException(TwoFactorSetupAlreadyCompleted::class);
        $this->expectExceptionMessage('user [1]');

        $this->handler->initialize2Fa($command);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_the_user_does_not_exist(): void
    {
        $codes = BackupCodes::generate();

        $command = new Initialize2Fa(212, 'super-secret-key', $codes);

        $this->expectException(UserNotFound::class);

        $this->handler->initialize2Fa($command);
    }
}
