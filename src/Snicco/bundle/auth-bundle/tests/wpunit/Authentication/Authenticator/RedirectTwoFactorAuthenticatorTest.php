<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authentication\Authenticator;

use Codeception\TestCase\WPTestCase;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\LoginResult;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\RedirectTwoFactorAuthenticator;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\BackupCodes;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemory2FaSettingsTwoFactor;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\StubUrlGenerator;
use WP_User;

/**
 * @internal
 */
final class RedirectTwoFactorAuthenticatorTest extends WPTestCase
{
    private ServerRequest                  $base_request;

    private RedirectTwoFactorAuthenticator $authenticator;

    private InMemory2FaSettingsTwoFactor   $two_factor_settings;

    protected function setUp(): void
    {
        parent::setUp();

        $response_factory = new ResponseFactory(new Psr17Factory(), new Psr17Factory());
        $url_g = new StubUrlGenerator([
            '2fa.challenge' => '/two-factor-challenge',
        ]);

        $this->authenticator = new RedirectTwoFactorAuthenticator(
            $this->two_factor_settings = new InMemory2FaSettingsTwoFactor([]),
            $response_factory,
            $url_g,
            '2fa.challenge',
        );

        $this->base_request = new ServerRequest('POST', '/login', [], null, '1.1', [
            'REQUEST_METHOD' => 'POST',
        ]);
    }

    /**
     * @test
     */
    public function that_the_authenticator_does_nothing_if_the_next_authenticator_is_not_successful(): void
    {
        $result = $this->authenticator->attempt(
            Request::fromPsr($this->base_request),
            fn (): LoginResult => LoginResult::failed(new Response(new \Nyholm\Psr7\Response(401)))
        );

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(new Response(new \Nyholm\Psr7\Response(401)), $result->response());
    }

    /**
     * @test
     */
    public function that_a_successful_authentication_from_a_following_authenticator_is_not_transformed_if_2fa_not_enabled_for_user(): void
    {
        $result = $this->authenticator->attempt(
            Request::fromPsr($this->base_request),
            fn (): LoginResult => new LoginResult(new WP_User(1))
        );

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->response());
    }

    /**
     * @test
     */
    public function that_the_user_is_not_authenticated_and_challenged_if_two_2fa_is_used(): void
    {
        $this->two_factor_settings->initiateSetup(1, 'secret', BackupCodes::fromPlainCodes());
        $this->two_factor_settings->completeSetup(1);

        $result = $this->authenticator->attempt(
            Request::fromPsr($this->base_request),
            fn (): LoginResult => new LoginResult(new WP_User(1))
        );

        $this->assertFalse($result->isSuccess());

        $response = $result->response();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/two-factor-challenge', $response->getHeaderLine('location'));
        $this->assertSame('1', $response->getHeaderLine(RedirectTwoFactorAuthenticator::CHALLENGED_USER_ID_HEADER));

        $this->expectException(LogicException::class);
        $result->authenticatedUser();
    }
}
