<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Fortress\Tests\integration\Auth\TwoFactor\Infrastructure;

use Closure;
use Codeception\TestCase\WPTestCase;
use Generator;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\Exception\InvalidOTPCode;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Fortress\Auth\TwoFactor\Infrastructure\Google2FaProvider;
use Snicco\Enterprise\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\Fortress\Tests\fixtures\MD5OTPValidator;

use function md5;
use function strlen;
use function substr;
use function time;

/**
 * @internal
 */
final class OTPValidatorTest extends WPTestCase
{
    private InMemoryTwoFactorSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new InMemoryTwoFactorSettings();
    }

    /**
     * @test
     *
     * @param Closure(TwoFactorSettings):OTPValidator $validator
     *
     * @dataProvider validatorWithCodes
     */
    public function that_an_exception_is_thrown_if_a_user_without_secret_is_validated(Closure $validator): void
    {
        $validator = $validator($this->settings);

        $this->expectException(InvalidOTPCode::class);
        $this->expectExceptionMessage('[1]');

        $validator->validate('123456', 1);
    }

    /**
     * @test
     *
     * @param Closure(TwoFactorSettings):OTPValidator $validator
     *
     * @dataProvider validatorWithCodes
     */
    public function that_an_invalid_otp_throws_an_exception(
        Closure $validator,
        string $user_secret,
        string $valid_code
    ): void {
        $validator = $validator($this->settings);

        $this->settings->add(1, [
            'secret' => $user_secret,
        ]);

        $invalid = (string) substr($valid_code, 0, strlen($valid_code) - 1) . 'a';

        $this->expectException(InvalidOTPCode::class);

        $validator->validate($invalid, 1);
    }

    /**
     * @test
     *
     * @param Closure(TwoFactorSettings):OTPValidator $validator
     *
     * @dataProvider validatorWithCodes
     */
    public function that_an_otp_can_be_validated(Closure $validator, string $user_secret, string $valid_code): void
    {
        $validator = $validator($this->settings);

        $this->settings->add(1, [
            'secret' => $user_secret,
        ]);

        $validator->validate($valid_code, 1);

        $this->assertSame(time(), $this->settings->lastUsedTimestamp(1));
    }

    /**
     * @test
     *
     * @param Closure(TwoFactorSettings):OTPValidator $validator
     *
     * @dataProvider validatorWithCodes
     */
    public function that_an_otp_can_be_validated_with_a_last_used_timestamp(
        Closure $validator,
        string $user_secret,
        string $valid_code
    ): void {
        $validator = $validator($this->settings);

        $this->settings->add(1, [
            'secret' => $user_secret,
            'last_used' => time() - 1000,
        ]);

        $validator->validate($valid_code, 1);

        $this->assertSame(time(), $this->settings->lastUsedTimestamp(1));
    }

    /**
     * @test
     *
     * @param Closure(TwoFactorSettings):OTPValidator $validator
     *
     * @dataProvider validatorWithCodes
     */
    public function that_an_otp_can_be_validated_exactly_once(
        Closure $validator,
        string $user_secret,
        string $valid_code
    ): void {
        $validator = $validator($this->settings);

        $this->settings->add(1, [
            'secret' => $user_secret,
        ]);

        $validator->validate($valid_code, 1);

        $this->expectException(InvalidOTPCode::class);

        $validator->validate($valid_code, 1);
    }

    public function validatorWithCodes(): Generator
    {
        $google_2fa = new Google2FA();

        yield 'google2a' => [
            fn (TwoFactorSettings $settings): Google2FaProvider => new Google2FaProvider($google_2fa, $settings),
            $secret = $google_2fa->generateSecretKey(),
            $google_2fa->getCurrentOtp($secret),
        ];

        yield 'in-memory-md5' => [
            fn (TwoFactorSettings $settings): MD5OTPValidator => new MD5OTPValidator($settings),
            md5('foobar'),
            'foobar',
        ];
    }
}
