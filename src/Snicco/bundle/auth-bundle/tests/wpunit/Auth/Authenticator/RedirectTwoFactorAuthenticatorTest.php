<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\wpunit\Auth\Authenticator;

use Codeception\TestCase\WPTestCase;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\LoginResult;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\RedirectTwoFactorAuthenticator;
use Snicco\Enterprise\AuthBundle\Auth\Authenticator\Domain\TwoFactorAuthenticator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\AuthBundle\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\AuthBundle\Tests\fixtures\StubUrlGenerator;
use Snicco\Enterprise\AuthBundle\Tests\fixtures\TwoFactorChallengeRepositoryInMemory;
use WP_User;

use function parse_str;
use function parse_url;

/**
 * @internal
 */
final class RedirectTwoFactorAuthenticatorTest extends WPTestCase
{
    private ServerRequest $base_request;

    private RedirectTwoFactorAuthenticator $authenticator;

    private InMemoryTwoFactorSettings $two_factor_settings;

    private TwoFactorChallengeService $two_factor_challenge_service;

    protected function setUp(): void
    {
        parent::setUp();

        $response_factory = new ResponseFactory(new Psr17Factory(), new Psr17Factory());
        $url_g = new StubUrlGenerator([
            '2fa.challenge' => '/two-factor-challenge',
        ]);

        $this->authenticator = new RedirectTwoFactorAuthenticator(
            $this->two_factor_settings = new InMemoryTwoFactorSettings([]),
            $this->two_factor_challenge_service = new TwoFactorChallengeService(
                'foo_secret',
                new TwoFactorChallengeRepositoryInMemory(),
            ),
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

        $this->assertFalse($result->isAuthenticated());
        $this->assertEquals(new Response(new \Nyholm\Psr7\Response(401)), $result->response());
    }

    /**
     * @test
     */
    public function that_a_successful_authentication_from_a_following_authenticator_is_not_transformed_if_2fa_not_enabled_for_user(
        ): void {
        $result = $this->authenticator->attempt(
            Request::fromPsr($this->base_request),
            fn (): LoginResult => new LoginResult(new WP_User(1))
        );

        $this->assertTrue($result->isAuthenticated());
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
            fn (): LoginResult => new LoginResult(new WP_User(1), true)
        );

        $this->assertFalse($result->isAuthenticated());

        $response = $result->response();

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $location = $response->getHeaderLine('location');

        $parts = parse_url($location);

        $this->assertTrue(isset($parts['path']), 'Missing path in url');
        $this->assertTrue(isset($parts['query']), 'Missing query in url');

        parse_str($parts['query'], $query);

        $this->assertTrue(
            isset($query[TwoFactorAuthenticator::CHALLENGE_ID]),
            'Missing token param in query string'
        );
        $this->assertTrue(isset($query['remember_me']), 'Missing remember_me param in query string');

        $this->assertSame('/two-factor-challenge', $parts['path']);
        $this->assertSame('1', $query['remember_me']);

        $token = (string) $query[TwoFactorAuthenticator::CHALLENGE_ID];

        $this->assertSame(1, $this->two_factor_challenge_service->getChallengedUser($token));

        $this->expectException(LogicException::class);
        $result->authenticatedUser();
    }
}
