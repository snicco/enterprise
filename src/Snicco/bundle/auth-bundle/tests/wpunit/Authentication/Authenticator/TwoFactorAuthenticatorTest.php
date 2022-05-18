<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authentication\Authenticator;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\ServerRequest;
use RuntimeException;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Enterprise\Bundle\Auth\Authentication\Authenticator\TwoFactorAuthenticator;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\FailedTwoFactorAuthentication;
use Snicco\Enterprise\Bundle\Auth\Authentication\RequestAttributes;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\BackupCodes;
use Snicco\Enterprise\Bundle\Auth\Authentication\User\WPUserProvider;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\InMemory2FaSettingsTwoFactor;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\StubTwoFactorCredentialsValidator;
use WP_User;

use function iterator_to_array;

/**
 * @internal
 */
final class TwoFactorAuthenticatorTest extends WPTestCase
{
    private ServerRequest $base_request;

    private WP_User $default_user;

    private TestableEventDispatcher $testable_dispatcher;

    private TwoFactorAuthenticator $authenticator;

    private InMemory2FaSettingsTwoFactor $two_factor_settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $this->authenticator = new TwoFactorAuthenticator(
            $this->testable_dispatcher,
            $this->two_factor_settings = new InMemory2FaSettingsTwoFactor([]),
            new StubTwoFactorCredentialsValidator(),
            new WPUserProvider()
        );
        $this->base_request = new ServerRequest('POST', '/login', [], null, '1.1', [
            'REQUEST_METHOD' => 'POST',
        ]);
        $this->default_user = new WP_User(1);
    }

    /**
     * @test
     */
    public function that_the_authenticator_does_nothing_if_no_challenged_user_is_set_on_the_request(): void
    {
        $request = Request::fromPsr($this->base_request);

        $this->expectExceptionMessage('Forced Exception');

        $this->authenticator->attempt($request, function (): void {
            throw new RuntimeException('Forced Exception');
        });
    }

    /**
     * @test
     */
    public function that_the_two_factor_authenticator_fails_for_invalid_credentials(): void
    {
        $result = $this->authenticator->attempt($this->aRequestThatDoesNotPass2Fa(1), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertFalse($result->isSuccess());

        $this->testable_dispatcher->assertDispatched(
            fn (FailedTwoFactorAuthentication $event): bool => 'Failed two-factor authentication for user [1]' === $event->message()
        );
    }

    /**
     * @test
     */
    public function that_the_two_factor_authenticator_authenticates_for_valid_credentials(): void
    {
        $result = $this->authenticator->attempt($this->aRequestThatPasses2Fa(1), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertNull($result->rememberUser());
    }

    /**
     * @test
     */
    public function that_the_remember_me_status_can_be_set(): void
    {
        $result = $this->authenticator->attempt($this->aRequestThatPasses2Fa(1, true), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertTrue($result->rememberUser());
    }

    /**
     * @test
     */
    public function that_the_remember_me_status_can_be_set_to_false(): void
    {
        $result = $this->authenticator->attempt($this->aRequestThatPasses2Fa(1, false), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertFalse($result->rememberUser());
    }

    /**
     * @test
     *
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    public function that_a_valid_backup_code_can_be_used_to_log_in(): void
    {
        $codes = BackupCodes::generate();
        $backup_codes = BackupCodes::fromPlainCodes($codes);

        $this->two_factor_settings->initiateSetup(1, 'secret', clone $backup_codes);

        $result = $this->authenticator->attempt($this->aRequestThatDoesNotPass2Fa(1)->withParsedBody([
            'backup_code' => $codes[0],
        ]), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());

        $new_codes = $this->two_factor_settings->getBackupCodes(1);

        $this->assertNotEquals(iterator_to_array($backup_codes), iterator_to_array($new_codes));

        // Other codes still works
        $result = $this->authenticator->attempt($this->aRequestThatDoesNotPass2Fa(1)->withParsedBody([
            'backup_code' => $codes[1],
        ]), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
    }

    /**
     * @test
     */
    public function that_an_invalid_code_does_not_log_the_user_in(): void
    {
        $codes = BackupCodes::generate();
        $backup_codes = BackupCodes::fromPlainCodes($codes);

        $this->two_factor_settings->initiateSetup(1, 'secret', clone $backup_codes);

        $result = $this->authenticator->attempt($this->aRequestThatDoesNotPass2Fa(1)->withParsedBody([
            'backup_code' => $codes[0] . 'a',
        ]), function (): void {
            throw new RuntimeException('Should not be called');
        });

        $this->assertFalse($result->isSuccess());

        $this->testable_dispatcher->assertDispatched(FailedTwoFactorAuthentication::class);
    }

    private function aRequestThatDoesNotPass2Fa(int $challenged_user): Request
    {
        return Request::fromPsr($this->base_request->withParsedBody([]))
            ->withAttribute(RequestAttributes::CHALLENGED_USER, $challenged_user);
    }

    private function aRequestThatPasses2Fa(int $challenged_user, bool $remember = null): Request
    {
        return Request::fromPsr($this->base_request->withParsedBody([
            'succeed_2fa' => true,
        ]))
            ->withAttribute(RequestAttributes::CHALLENGED_USER, $challenged_user)
            ->withAttribute(RequestAttributes::REMEMBER_CHALLENGED_USER, $remember);
    }
}
