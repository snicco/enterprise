<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Authenticator;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\ServerRequest;
use RuntimeException;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\SignedUrl\HMAC;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\SignedUrlValidator;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Enterprise\Bundle\Auth\Authenticator\MagicLinkAuthenticator;
use Snicco\Enterprise\Bundle\Auth\Event\FailedMagicLinkAuthentication;
use Snicco\Enterprise\Bundle\Auth\User\WPUserProvider;
use WP_User;
use function sprintf;

/**
 * @internal
 *
 * @psalm-suppress ArgumentTypeCoercion
 */
final class MagicLinkAuthenticatorTest extends WPTestCase
{
    private UrlSigner               $url_signer;

    private MagicLinkAuthenticator  $authenticator;

    private WP_User                 $default_user;

    private TestableEventDispatcher $testable_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->url_signer = new UrlSigner(
            $storage = new InMemoryStorage(),
            $hmac = new HMAC(Secret::generate(), 'sha256')
        );
        $this->testable_dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $validator = new SignedUrlValidator($storage, $hmac);
        $this->authenticator = new MagicLinkAuthenticator($this->testable_dispatcher, $validator, new WPUserProvider());
        $this->default_user = new WP_User(1);
    }

    /**
     * @test
     */
    public function that_an_invalid_magic_link_cant_authenticate_the_user(): void
    {
        $link = $this->url_signer->sign('/login?user_id=1', 10);
        $request = new ServerRequest('GET', $link->asString() . 'a');
        $request = $request->withQueryParams([
            'user_id' => '1',
        ]);

        $result = $this->authenticator->attempt(Request::fromPsr($request), function (): void {
            throw new RuntimeException('Should have failed');
        });

        $this->assertFalse($result->isSuccess());
        $this->testable_dispatcher->assertDispatched(
            fn (FailedMagicLinkAuthentication $event): bool => 'Failed authentication with magic link for user [1]' === $event->message()
        );
    }

    /**
     * @test
     */
    public function that_a_valid_magic_link_authenticates_the_user(): void
    {
        $link = $this->url_signer->sign(sprintf('/login?user_id=%s', $this->default_user->ID), 10);
        $request = new ServerRequest('GET', $link->asString());
        $request = $request->withQueryParams([
            'user_id' => (string) $this->default_user->ID,
        ]);

        $result = $this->authenticator->attempt(Request::fromPsr($request), function (): void {
            throw new RuntimeException('Should have failed');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($this->default_user, $result->authenticatedUser());
        $this->assertNull($result->rememberUser());
        $this->testable_dispatcher->assertNotDispatched(FailedMagicLinkAuthentication::class);
    }

    /**
     * @test
     */
    public function that_remember_me_can_be_set_to_true(): void
    {
        $link = $this->url_signer->sign(sprintf('/login?remember_me=1&user_id=%s', $this->default_user->ID), 10);
        $request = new ServerRequest('GET', $link->asString());
        $request = $request->withQueryParams([
            'user_id' => (string) $this->default_user->ID,
            'remember_me' => '1',
        ]);

        $result = $this->authenticator->attempt(Request::fromPsr($request), function (): void {
            throw new RuntimeException('Should have failed');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->rememberUser());
    }

    /**
     * @test
     */
    public function that_remember_me_can_be_set_to_false(): void
    {
        $link = $this->url_signer->sign(sprintf('/login?remember_me=0&user_id=%s', $this->default_user->ID), 10);
        $request = new ServerRequest('GET', $link->asString());
        $request = $request->withQueryParams([
            'user_id' => (string) $this->default_user->ID,
            'remember_me' => '0',
        ]);

        $result = $this->authenticator->attempt(Request::fromPsr($request), function (): void {
            throw new RuntimeException('Should have failed');
        });

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->rememberUser());
    }
}
