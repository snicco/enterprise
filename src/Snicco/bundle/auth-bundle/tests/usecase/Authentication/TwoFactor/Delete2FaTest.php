<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\usecase\Authentication\TwoFactor;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\Delete2Fa\Delete2FaSettings;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\MD5OTPValidator;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemoryTwoFactorSettings;

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
}
