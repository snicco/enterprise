<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Auth\TwoFactor\Infrastructure;

use Codeception\TestCase\WPTestCase;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Bundle\Testing\Functional\Concerns\CreateWordPressUsers;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Enterprise\Bundle\Fortress\Auth\Authenticator\Domain\TwoFactorAuthenticator;
use Snicco\Enterprise\Bundle\Fortress\Auth\AuthModuleOption;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Domain\TwoFactorChallengeService;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateChallengeRedirectShutdownPHP;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateChallengeUser;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\Event\WPAuthenticateRedirectContext;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Webmozart\Assert\Assert;
use WP_Error;
use WP_UnitTest_Factory;
use WP_UnitTest_Factory_For_User;

use function add_filter;
use function dirname;
use function is_string;
use function parse_str;
use function parse_url;
use function remove_all_filters;
use function sprintf;
use function wp_signon;

use const PHP_URL_QUERY;

/**
 * @internal
 */
final class TwoFactorChallenges_wp_authenticate_Test extends WPTestCase
{
    use CreateWordPressUsers;
    use BundleTestHelpers;

    private Kernel $kernel;

    private DIContainer $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $c): void {
            $c->set('fortress.auth.' . AuthModuleOption::TWO_FACTOR_SETTINGS_TABLE_BASENAME, 'auth_2fa_settings');
            $c->set('fortress.auth.' . AuthModuleOption::TWO_FACTOR_CHALLENGES_TABLE_BASENAME, 'auth_2fa_challenges');
        });
        $this->container = $this->kernel->container();
        TwoFactorSettingsBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'wp_auth_2fa_settings');
        TwoFactorChallengeRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'wp_auth_2fa_challenges');
        unset($_SERVER['HTTP_REFERER'], $_REQUEST['redirect_to'], $_REQUEST['remember_me'], $_REQUEST['rememberme'], $_REQUEST['remember']);
    }

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_auth_2fa_settings');
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_auth_2fa_challenges');
        unset($_SERVER['HTTP_REFERER'], $_REQUEST['redirect_to'], $_REQUEST['remember_me'], $_REQUEST['rememberme'], $_REQUEST['remember']);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function that_a_user_without_2fa_setup_can_be_authenticated_through_wp_signon(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');
        remove_all_filters('set_auth_cookie');

        $user = $this->createAdmin([
            'user_pass' => 'pw',
        ]);

        $result = wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'pw',
        ]);

        $this->assertEquals($user, $result);
    }

    /**
     * @test
     */
    public function that_a_user_with_incomplete_2fa_setup_can_be_authenticated_through_wp_signon(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var CommandBus $bus */
        $bus = $this->kernel->container()
            ->get(CommandBus::class);

        $bus->handle(
            new Initialize2Fa(
                1,
                'foo',
                BackupCodes::generate()
            )
        );

        $user = $this->createAdmin([
            'user_pass' => 'pw',
        ]);

        $result = wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'pw',
        ]);

        $this->assertEquals($user, $result);
    }

    /**
     * @test
     */
    public function that_nothing_happens_for_a_user_with_completed_2fa_setup_but_invalid_credentials_passed_to_wp_signon(
        ): void {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        $this->userHasTwo2FaCompleted(1);

        $user = $this->createAdmin([
            'user_pass' => 'pw',
        ]);

        $result = wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'bogus',
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * @test
     */
    public function that_a_user_with_2fa_completed_is_redirected_and_php_is_shut_down(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->container->get(EventDispatcher::class);

        $user = $this->createAdmin([
            'user_pass' => 'foobar',
        ]);

        $this->userHasTwo2FaCompleted($user->ID);

        $redirect_url = null;

        $testable_dispatcher->listen(function (WPAuthenticateChallengeRedirectShutdownPHP $event) use (
            &$redirect_url
        ): void {
            $event->do_shutdown = false;
            $redirect_url = $event->redirect_url;
        });

        $_SERVER['HTTP_REFERER'] = '/my-account';

        wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'foobar',
        ]);

        $this->assertIsString($redirect_url);

        parse_str((string) parse_url($redirect_url, PHP_URL_QUERY), $query_vars);

        $query_param = TwoFactorAuthenticator::CHALLENGE_ID;

        $this->assertTrue(
            isset($query_vars[$query_param]) && is_string($query_vars[$query_param]),
            sprintf('Challenge url does not have query param [%s]', $query_param)
        );
        $this->assertTrue(
            isset($query_vars['redirect_to']) && is_string($query_vars['redirect_to']),
            'The redirect_to query param was not set even tho a HTTP Referrer was present.'
        );

        $this->assertSame('/my-account', $query_vars['redirect_to']);
        $this->assertArrayNotHasKey('remember_me', $query_vars, 'Remember_me query var should not be set by default.');

        /** @var TwoFactorChallengeService $challenge_service */
        $challenge_service = $this->container->get(TwoFactorChallengeService::class);

        $this->assertSame($user->ID, $challenge_service->getChallengedUser($query_vars[$query_param]));
    }

    /**
     * @test
     */
    public function that_a_redirect_to_request_parameter_has_priority_over_the_http_referrer(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->container->get(EventDispatcher::class);

        $user = $this->createAdmin([
            'user_pass' => 'foobar',
        ]);

        $this->userHasTwo2FaCompleted($user->ID);

        $redirect_url = null;

        $testable_dispatcher->listen(function (WPAuthenticateChallengeRedirectShutdownPHP $event) use (
            &$redirect_url
        ): void {
            $event->do_shutdown = false;
            $redirect_url = $event->redirect_url;
        });

        $_SERVER['HTTP_REFERER'] = '/my-account';
        $_REQUEST['redirect_to'] = '/dashboard';

        wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'foobar',
        ]);

        $this->assertIsString($redirect_url);

        parse_str((string) parse_url($redirect_url, PHP_URL_QUERY), $query_vars);

        $this->assertTrue(
            isset($query_vars['redirect_to']) && is_string($query_vars['redirect_to']),
            'The redirect_to query param was not set even tho a HTTP Referrer was present.'
        );

        $this->assertSame('/dashboard', $query_vars['redirect_to']);
    }

    /**
     * @test
     */
    public function that_the_remember_me_value_is_set_correctly_if_present_in_the_request(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->container->get(EventDispatcher::class);

        $user = $this->createAdmin([
            'user_pass' => 'foobar',
        ]);

        $this->userHasTwo2FaCompleted($user->ID);

        $redirect_url = null;

        $testable_dispatcher->listen(function (WPAuthenticateChallengeRedirectShutdownPHP $event) use (
            &$redirect_url
        ): void {
            $event->do_shutdown = false;
            $redirect_url = $event->redirect_url;
        });

        $_REQUEST['remember_me'] = '1';

        wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'foobar',
        ]);

        $this->assertIsString($redirect_url);

        parse_str((string) parse_url($redirect_url, PHP_URL_QUERY), $query_vars);

        $this->assertTrue(
            isset($query_vars['remember_me']) && is_string($query_vars['remember_me']),
            'The remember_me query param was not set.'
        );

        $this->assertSame('1', $query_vars['remember_me']);
    }

    /**
     * @test
     */
    public function that_the_redirect_and_remember_me_values_can_be_customized_at_runtime(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->container->get(EventDispatcher::class);

        $user = $this->createAdmin([
            'user_pass' => 'foobar',
        ]);

        $this->userHasTwo2FaCompleted($user->ID);

        $redirect_url = null;
        $testable_dispatcher->listen(function (WPAuthenticateChallengeRedirectShutdownPHP $event) use (
            &$redirect_url
        ): void {
            $event->do_shutdown = false;
            $redirect_url = $event->redirect_url;
        });

        $_REQUEST['redirect_to'] = '/dashboard';

        add_filter(WPAuthenticateRedirectContext::class, function (WPAuthenticateRedirectContext $event) {
            $this->assertSame('/dashboard', $event->initial_parsed_redirect);
            $this->assertNull($event->initial_parsed_remember_me);

            $event->redirect_to = '/dashboard-custom';
            $event->remember_me = true;
        });

        wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'foobar',
        ]);

        $this->assertIsString($redirect_url);

        parse_str((string) parse_url($redirect_url, PHP_URL_QUERY), $query_vars);

        $this->assertTrue(
            isset($query_vars['redirect_to']) && is_string($query_vars['redirect_to']),
            'The redirect_to query param was not set even tho a HTTP Referrer was present.'
        );
        $this->assertSame('/dashboard-custom', $query_vars['redirect_to']);

        $this->assertTrue(
            isset($query_vars['remember_me']) && is_string($query_vars['remember_me']),
            'The remember_me query param was not set.'
        );
        $this->assertSame('1', $query_vars['remember_me']);
    }

    /**
     * @test
     */
    public function that_the_two_factor_redirect_can_be_prevented(): void
    {
        $this->kernel->boot();
        remove_all_filters('set_logged_in_cookie');

        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->container->get(EventDispatcher::class);

        $user = $this->createAdmin([
            'user_pass' => 'foobar',
        ]);

        $this->preventShutdown($testable_dispatcher);
        $this->userHasTwo2FaCompleted($user->ID);

        $testable_dispatcher->listen(function (WPAuthenticateChallengeUser $event) use ($user): void {
            $this->assertEquals($user, $event->user);
            $this->assertTrue($event->challenge_user);
            $event->challenge_user = false;
        });

        $auth_user = wp_signon([
            'user_login' => $user->user_login,
            'user_password' => 'foobar',
        ]);

        $this->assertEquals($user, $auth_user);

        $testable_dispatcher->assertNotDispatched(WPAuthenticateChallengeRedirectShutdownPHP::class);
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 4) . '/fixtures/test-app';
    }

    protected function userFactory(): WP_UnitTest_Factory_For_User
    {
        $factory = $this->factory();
        Assert::isInstanceOf($factory, WP_UnitTest_Factory::class);

        return $factory->user;
    }

    private function userHasTwo2FaCompleted(int $user_id): void
    {
        Assert::positiveInteger($user_id);

        /** @var CommandBus $bus */
        $bus = $this->kernel->container()
            ->get(CommandBus::class);

        $google_2fa = new Google2FA();
        /** @var non-empty-string $secret */
        $secret = $google_2fa->generateSecretKey();

        $bus->handle(
            new Initialize2Fa(
                $user_id,
                $secret,
                BackupCodes::generate()
            )
        );
        $otp = $google_2fa->getCurrentOtp($secret);
        $bus->handle(new Complete2FaSetup($user_id, $otp));
    }

    private function preventShutdown(TestableEventDispatcher $testable_dispatcher): void
    {
        $testable_dispatcher->listen(function (WPAuthenticateChallengeRedirectShutdownPHP $event): void {
            $event->do_shutdown = false;
        });
    }
}
