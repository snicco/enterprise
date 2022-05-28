<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\usecase\Auth\TwoFactor;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Delete2Fa\Delete2FaSettings;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Fortress\Auth\User\Domain\UserNotFound;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\MD5OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\StubUserExistsProvider as StubUserExistsProviderAlias;

/**
 * @internal
 */
final class Delete2FaTest extends Unit
{
    private TwoFactorCommandHandler $handler;

    private InMemoryTwoFactorSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new InMemoryTwoFactorSettings();
        $this->handler = new TwoFactorCommandHandler(
            $this->settings,
            new StubUserExistsProviderAlias([1]),
            new MD5OTPValidator($this->settings)
        );
    }

    /**
     * @test
     */
    public function that_two_factor_settings_can_be_deleted_for_a_user(): void
    {
        $this->settings->add(1, [
            'secret' => 'super-secret',
        ]);

        $this->assertTrue($this->settings->isSetupCompleteForUser(1));

        $this->handler->delete2Fa(new Delete2FaSettings(1));

        $this->assertFalse($this->settings->isSetupCompleteForUser(1));
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_for_missing_users(): void
    {
        $this->expectException(UserNotFound::class);
        $this->handler->delete2Fa(new Delete2FaSettings(12));
    }
}
