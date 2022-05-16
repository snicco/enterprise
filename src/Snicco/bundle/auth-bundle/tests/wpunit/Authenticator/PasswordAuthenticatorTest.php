<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authenticator;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\ServerRequest;
use RuntimeException;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Authenticator\PasswordAuthenticator;
use Snicco\Enterprise\Bundle\Auth\Event\FailedPasswordAuthentication;
use Snicco\Enterprise\Bundle\Auth\User\WPUserProvider;
use WP_User;
use function sprintf;

/**
 * @internal
 */
final class PasswordAuthenticatorTest extends WPTestCase
{
    private PasswordAuthenticator $password_authenticator;

    private ServerRequest $base_request;

    private WP_User $default_user;

    private string $default_password;

    private TestableEventDispatcher $testable_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->password_authenticator = new PasswordAuthenticator($this->testable_dispatcher, new WPUserProvider());
        $this->base_request = new ServerRequest('POST', '/login', [], null, '1.1', [
            'REQUEST_METHOD' => 'POST',
        ]);
        $this->default_user = new WP_User(1);
        $this->default_password = 'password';
    }

    /**
     * @test
     */
    public function that_the_authenticator_does_nothing_if_the_pwd_key_is_missing(): void
    {
        $request = Request::fromPsr($this->base_request);

        $this->expectExceptionMessage('Forced exception');

        $this->password_authenticator->attempt($request->withParsedBody([
            'log' => 'foo',
        ]), function (): void {
            throw new RuntimeException('Forced exception');
        });
    }

    /**
     * @test
     */
    public function that_the_authenticator_does_nothing_if_the_log_key_is_missing(): void
    {
        $request = Request::fromPsr($this->base_request);

        $this->expectExceptionMessage('Forced exception');

        $this->password_authenticator->attempt($request->withParsedBody([
            'pwd' => 'foo',
        ]), function (): void {
            throw new RuntimeException('Forced exception');
        });
    }

    /**
     * @test
     */
    public function that_the_authenticator_does_nothing_if_the_request_method_is_not_post(): void
    {
        $request = Request::fromPsr($this->base_request->withMethod('GET'));

        $this->expectExceptionMessage('Forced exception');

        $this->password_authenticator->attempt($request->withParsedBody([
            'pwd' => 'foo',
            'log' => 'bar',
        ]), function (): void {
            throw new RuntimeException('Forced exception');
        });
    }

    /**
     * @test
     */
    public function that_invalid_username_credentials_dispatch_a_login_failed_event(): void
    {
        $request = Request::fromPsr($this->base_request);
        $request = $request->withParsedBody([
            'log' => $this->default_user->user_login . 'bogus',
            'pwd' => $this->default_password,
        ]);

        $result = $this->password_authenticator->attempt($request, function (): void {
            throw new RuntimeException('User should have been authenticated.');
        });

        $this->assertFalse($result->isSuccess());

        $this->testable_dispatcher->assertDispatched(function (FailedPasswordAuthentication $event): bool {
            $user = $this->default_user->user_login . 'bogus';

            return sprintf('Failed password authentication attempt for user [%s]', $user) === $event->message();
        });
    }

    /**
     * @test
     */
    public function that_invalid_password_credentials_dispatch_a_login_failed_event(): void
    {
        $request = Request::fromPsr($this->base_request);
        $request = $request->withParsedBody([
            'log' => $this->default_user->user_login,
            'pwd' => $this->default_password . 'bogus',
        ]);

        $result = $this->password_authenticator->attempt($request, function (): void {
            throw new RuntimeException('User should have been authenticated.');
        });

        $this->assertFalse($result->isSuccess());

        $this->testable_dispatcher->assertDispatched(function (FailedPasswordAuthentication $event): bool {
            $user = $this->default_user->user_login;

            return sprintf('Failed password authentication attempt for user [%s]', $user) === $event->message();
        });
    }

    /**
     * @test
     */
    public function that_a_user_can_be_authenticated_with_correct_credentials(): void
    {
        $request = Request::fromPsr($this->base_request);
        $request = $request->withParsedBody([
            'log' => $this->default_user->user_login,
            'pwd' => $this->default_password,
        ]);

        $result = $this->password_authenticator->attempt($request, function (): void {
            throw new RuntimeException('User should have been authenticated.');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertNull($result->rememberUser());
    }

    /**
     * @test
     */
    public function that_a_user_can_be_authenticated_with_remember_me(): void
    {
        $request = Request::fromPsr($this->base_request);
        $request = $request->withParsedBody([
            'log' => $this->default_user->user_login,
            'pwd' => $this->default_password,
            'remember_me' => '1',
        ]);

        $result = $this->password_authenticator->attempt($request, function (): void {
            throw new RuntimeException('User should have been authenticated.');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertTrue($result->rememberUser());
        $this->assertNull($result->response());
    }

    /**
     * @test
     */
    public function that_a_user_can_be_authenticated_with_remember_me_set_to_false(): void
    {
        $request = Request::fromPsr($this->base_request);
        $request = $request->withParsedBody([
            'log' => $this->default_user->user_login,
            'pwd' => $this->default_password,
            'remember_me' => '0',
        ]);

        $result = $this->password_authenticator->attempt($request, function (): void {
            throw new RuntimeException('User should have been authenticated.');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertFalse($result->rememberUser());
        $this->assertNull($result->response());
    }
}
