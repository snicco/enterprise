<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Auth\TwoFactor\Infrastructure\Http\Controller;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\InMemoryTwoFactorSettings;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\MD5OTPValidator;
use Snicco\Enterprise\Bundle\Fortress\Tests\fixtures\TwoFactorChallengeRepositoryInMemory;
use Snicco\Enterprise\Bundle\Fortress\Tests\FortressWebTestCase;
use Webmozart\Assert\Assert;
use WP_User;

use function admin_url;
use function is_string;
use function md5;
use function remove_all_filters;
use function substr;

/**
 * @internal
 */
final class TwoFactorChallengeControllerTest extends FortressWebTestCase
{
    private InMemoryTwoFactorSettings $two_factor_settings;

    private TestClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->swapInstance(TwoFactorChallengeRepository::class, new TwoFactorChallengeRepositoryInMemory());
        $this->swapInstance(TwoFactorSettings::class, $this->two_factor_settings = new InMemoryTwoFactorSettings());
        $this->swapInstance(OTPValidator::class, new MD5OTPValidator($this->two_factor_settings));
        $this->swapInstance(TestClock::class, $this->clock = new TestClock());
    }

    /**
     * @test
     */
    public function that_a_challenge_displays_a_view_response(): void
    {
        $user = $this->createAdmin();

        $challenge_token = $this->getChallengeService()
            ->createChallenge($user->ID, 10);

        $browser = $this->getBrowser();

        $browser->request('GET', "/auth/two-factor/challenge?challenge_id={$challenge_token}");

        $response = $browser->lastResponse();

        $response->assertOk();

        $view_response = $response->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $view_response);

        $data = $view_response->viewData();
        $this->assertTrue(isset($data['request']) && $data['request'] instanceof Request, 'Request not set in view.');
        unset($data['request']);

        $this->assertTrue(
            isset($data['hidden_input_fields']) && is_string($data['hidden_input_fields']),
            'Hidden input fields not set in view.'
        );
        $this->assertStringContainsString(admin_url(), $data['hidden_input_fields']);
        $this->assertStringContainsString($challenge_token, $data['hidden_input_fields']);
        unset($data['hidden_input_fields']);

        $this->assertSame('snicco/fortress/2fa/challenge', $view_response->view());
        $this->assertEquals([
            'post_url' => '/auth/two-factor/challenge',
            'challenge_id' => $challenge_token,
            'redirect_to' => admin_url(),
            'remember_me' => false,
        ], $data);
    }

    /**
     * @test
     */
    public function that_a_custom_redirect_query_param_is_passed_to_the_view(): void
    {
        $user = $this->createAdmin();

        $challenge_token = $this->getChallengeService()
            ->createChallenge($user->ID, 10);

        $browser = $this->getBrowser();

        $browser->request(
            'GET',
            "/auth/two-factor/challenge?challenge_id={$challenge_token}&redirect_to=/foo&remember_me=1"
        );

        $response = $browser->lastResponse();

        $response->assertOk();

        $view_response = $response->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $view_response);

        $this->assertSame('/foo', $view_response->viewData()['redirect_to'] ?? '');
        $this->assertTrue($view_response->viewData()['remember_me'] ?? false);
    }

    /**
     * @test
     */
    public function that_logged_in_users_cant_access_the_route(): void
    {
        $user = $this->createAdmin();

        $this->loginAs($user);

        $challenge_token = $this->getChallengeService()
            ->createChallenge($user->ID, 10);

        $browser = $this->getBrowser();
        $browser->followRedirects(false);

        $browser->request(
            'GET',
            "/auth/two-factor/challenge?challenge_id={$challenge_token}&redirect_to=/foo&remember_me=1"
        );

        $response = $browser->lastResponse();

        $response->assertRedirectPath('/');
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_json_is_not_accepted(): void
    {
        $browser = $this->getBrowser();

        $browser->followRedirects(false);

        $browser->request('POST', '/auth/two-factor/challenge', [
        ], [], [
            'HTTP_ACCEPT' => 'text/html',
        ]);

        $browser->lastResponse()
            ->assertStatus(406);

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_an_expired_challenge_produces_a_410(): void
    {
        $user = $this->createAdmin();

        $challenge = $this->getChallengeService()
            ->createChallenge($user->ID, 1);

        $browser = $this->getBrowser();

        $this->clock->travelIntoFuture(2);

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => $challenge,
        ]);

        $browser->lastResponse()
            ->assertStatus(410);

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_a_non_existing_selector_returns_a_403(): void
    {
        $user = $this->createAdmin();

        $challenge = $this->getChallengeService()
            ->createChallenge($user->ID, 1);

        $browser = $this->getBrowser();

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => (string) substr($challenge, 2) . 'aa',
        ]);

        $browser->lastResponse()
            ->assertStatus(403);

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_a_tampered_verifier_returns_a_403(): void
    {
        $user = $this->createAdmin();

        $challenge = $this->getChallengeService()
            ->createChallenge($user->ID);

        $browser = $this->getBrowser();

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => (string) substr($challenge, 0, -2) . 'aa',
        ]);

        $browser->lastResponse()
            ->assertStatus(403);

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_a_malformed_token_returns_a_422(): void
    {
        $browser = $this->getBrowser();

        $this->clock->travelIntoFuture(2);

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => 'foobar',
        ]);

        $browser->lastResponse()
            ->assertStatus(422);

        $this->assertIsGuest();

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_an_invalid_otp_does_not_authenticate_the_user(): void
    {
        $user = $this->createAdmin();

        $this->userHas2FaSetupCompleted($user, md5('foo'));

        $challenge_token = $this->getChallengeService()
            ->createChallenge($user->ID, 10);

        $browser = $this->getBrowser();

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => $challenge_token,
            'otp' => 'bar',
        ]);

        $response = $browser->lastResponse();

        $response->assertUnauthorized();
        $response->assertIsJson();
        $response->assertExactJson([
            'message' => 'Invalid one-time-password. Please try again.',
        ]);

        $this->assertIsGuest();
    }

    /**
     * @test
     */
    public function that_a_challenge_can_be_completed_with_a_valid_code(): void
    {
        $user = $this->createAdmin();

        $this->userHas2FaSetupCompleted($user, md5('foo'));

        $challenge_token = $this->getChallengeService()
            ->createChallenge($user->ID, 10);

        $browser = $this->getBrowser();

        // Must remove our mapped events here because WPBrowser overwrites this function.
        remove_all_filters('set_logged_in_cookie');

        $browser->jsonRequest('POST', '/auth/two-factor/challenge', [
            'challenge_id' => $challenge_token,
            'otp' => 'foo',
            'redirect_to' => '/foo',
        ]);

        $response = $browser->lastResponse();

        $response->assertOk();
        $response->assertIsJson();
        $response->assertExactJson([
            'redirect_url' => '/foo',
        ]);

        $this->assertIsAuthenticated($user);

        $this->logout();

        $browser->reload();
        $response = $browser->lastResponse();
        $response->assertStatus(403);
        $this->assertIsGuest();
    }

    private function getChallengeService(): TwoFactorChallengeService
    {
        return $this->getBootedKernel()
            ->container()
            ->make(TwoFactorChallengeService::class);
    }

    private function userHas2FaSetupCompleted(WP_User $user, string $secret): void
    {
        $id = $user->ID;
        Assert::positiveInteger($id);
        $this->two_factor_settings->add($id, [
            'secret' => $secret,
        ]);
    }
}
