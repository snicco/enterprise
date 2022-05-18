<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authentication\TwoFactor;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\ServerRequest;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Google2FaValidator;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\InvalidTwoFactorCredentials;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemory2FaSettingsTwoFactor;
use WP_User;

use function time;

/**
 * @internal
 */
final class Google2FaValidatorTest extends WPTestCase
{
    private Request $base_request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_request = Request::fromPsr(new ServerRequest('POST', '/login', [], null, '1.1', [
            'REQUEST_METHOD' => 'POST',
        ]));
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_a_user_without_secret_is_validated(): void
    {
        $validator = new Google2FaValidator(new Google2FA(), new InMemory2FaSettingsTwoFactor([]));

        $this->expectException(InvalidTwoFactorCredentials::class);
        $this->expectExceptionMessage('User with id [1] did not complete 2FA setup yet.');

        $validator->validate($this->base_request->withParsedBody([
            'totp' => '123456',
        ]), new WP_User(1));
    }

    /**
     * @test
     */
    public function that_an_otp_can_be_validated(): void
    {
        $google_2fa = new Google2FA();
        $user_secret = $google_2fa->generateSecretKey();

        $validator = new Google2FaValidator($google_2fa, $setup = new InMemory2FaSettingsTwoFactor([
            1 => [
                'secret' => $user_secret,
            ],
        ]));

        $valid_otp = $google_2fa->getCurrentOtp($user_secret);

        $validator->validate($this->base_request->withParsedBody([
            'otp' => $valid_otp,
        ]), new WP_User(1));

        $this->assertSame(time(), $setup->lastUsedTimestamp(1));
    }

    /**
     * @test
     */
    public function that_an_otp_can_be_validated_with_a_last_use_ts(): void
    {
        $google_2fa = new Google2FA();
        $user_secret = $google_2fa->generateSecretKey();

        $validator = new Google2FaValidator($google_2fa, $setup = new InMemory2FaSettingsTwoFactor([
            1 => [
                'secret' => $user_secret,
                'last_used' => time() - 1000,
            ],
        ]));

        $valid_otp = $google_2fa->getCurrentOtp($user_secret);

        $validator->validate($this->base_request->withParsedBody([
            'otp' => $valid_otp,
        ]), new WP_User(1));

        $this->assertSame(time(), $setup->lastUsedTimestamp(1));
    }

    /**
     * @test
     */
    public function that_an_invalid_otp_throws_an_exception(): void
    {
        $google_2fa = new Google2FA();
        $user_secret = $google_2fa->generateSecretKey();

        $validator = new Google2FaValidator($google_2fa, new InMemory2FaSettingsTwoFactor([
            1 => [
                'secret' => $user_secret,
            ],
        ]));

        $valid_otp = $google_2fa->getCurrentOtp($user_secret) . 'bogus';

        $this->expectException(InvalidTwoFactorCredentials::class);

        $validator->validate($this->base_request->withParsedBody([
            'otp' => $valid_otp,
        ]), new WP_User(1));
    }

    /**
     * @test
     */
    public function that_one_otp_can_be_used_exactly_once(): void
    {
        $google_2fa = new Google2FA();
        $user_secret = $google_2fa->generateSecretKey();

        $validator = new Google2FaValidator($google_2fa, $setup = new InMemory2FaSettingsTwoFactor([
            1 => [
                'secret' => $user_secret,
            ],
        ]));

        $valid_otp = $google_2fa->getCurrentOtp($user_secret);
        $validator->validate($this->base_request->withParsedBody([
            'otp' => $valid_otp,
        ]), new WP_User(1));
        $this->assertSame(time(), $setup->lastUsedTimestamp(1));

        $this->expectException(InvalidTwoFactorCredentials::class);
        $validator->validate($this->base_request->withParsedBody([
            'otp' => $valid_otp,
        ]), new WP_User(1));
    }
}
