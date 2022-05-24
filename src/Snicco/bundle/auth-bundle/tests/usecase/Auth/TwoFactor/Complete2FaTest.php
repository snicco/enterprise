<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\usecase\Auth\TwoFactor;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\TwoFactorSetupIsNotInitialized;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Domain\Exception\TwoFactorSetupAlreadyCompleted;
use Snicco\Enterprise\Bundle\Auth\Auth\TwoFactor\Application\TwoFactorCommandHandler;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\MD5OTPValidator;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemoryTwoFactorSettings;

use function md5;

/**
 * @internal
 */
final class Complete2FaTest extends Unit
{
    private TwoFactorCommandHandler $handler;

    private InMemoryTwoFactorSettings $settings;

    private string $valid_otp_code;
    
    private string $invalid_otp;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->valid_otp_code = '123456';
        $this->invalid_otp = '654321';
        $this->settings = new InMemoryTwoFactorSettings([
            1 => ['secret' => md5($this->valid_otp_code), 'complete' => false],
            2 => ['secret' => md5($this->valid_otp_code), 'complete' => true]
        ]);
        $this->handler = new TwoFactorCommandHandler(
            $this->settings,
            new MD5OTPValidator($this->settings)
        );
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_can_be_completed_with_a_correct_code(): void
    {
        $this->assertFalse($this->settings->isSetupCompleteForUser(1));

        $this->handler->complete2FaSetup(new Complete2FaSetup(1, $this->valid_otp_code));

        $this->assertTrue($this->settings->isSetupCompleteForUser(1));
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_can_not_be_completed_without_being_initialized(): void
    {
        $this->expectException(TwoFactorSetupIsNotInitialized::class);
        $this->expectExceptionMessage('user [3]');

        $this->handler->complete2FaSetup(new Complete2FaSetup(3, $this->valid_otp_code));
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_can_not_be_completed_twice(): void
    {
        $this->expectException(TwoFactorSetupAlreadyCompleted::class);
        $this->expectExceptionMessage('user [2]');

        $this->handler->complete2FaSetup(new Complete2FaSetup(2, $this->valid_otp_code));
    }

    /**
     * @test
     */
    public function that_the_two_factor_setup_can_not_be_completed_with_an_invalid_code(): void
    {
        $this->expectException(InvalidOTPCode::class);
        $this->expectExceptionMessage('user [1]');

        $this->handler->complete2FaSetup(new Complete2FaSetup(1, $this->invalid_otp));
    }
}
