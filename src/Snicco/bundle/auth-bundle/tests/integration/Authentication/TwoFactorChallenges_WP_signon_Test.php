<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\integration\Authentication;

use WP_User;
use WP_Error;
use stdClass;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\Kernel\Kernel;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Component\EventDispatcher\GenericEvent;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\WPSignonLogin2FaChallengeRedirect;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\Bundle\Auth\Authentication\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;

use function dirname;
use function wp_signon;

final class TwoFactorChallenges_WP_signon_Test extends WPTestCase
{
    
    use BundleTestHelpers;
    
    private Kernel $kernel;
    
    protected function setUp() :void
    {
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $c) {
            $c->set('snicco_auth.authentication.table_names.2fa_settings', 'auth_2fa_settings');
        });
        parent::setUp();
        (new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), 'wp_auth_2fa_settings'))->createTable();
    }
    
    protected function tearDown() :void
    {
        $this->bundle_test->tearDownDirectories();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_auth_2fa_settings');
        parent::tearDown();
    }
    
    /**
     * @test
     */
    public function that_a_user_without_2fa_setup_can_be_authenticated_through_wp_signon() :void
    {
        $this->kernel->boot();
        
        $user = new WP_User(1);
        
        $result = wp_signon(['user_login' => $user->user_login, 'user_password'=> 'password']);
        
        $this->assertEquals($user, $result);
    }
    
    /**
     * @test
     */
    public function that_a_user_with_incomplete_2fa_setup_can_be_authenticated_through_wp_signon() :void
    {
        $this->kernel->boot();
        
        /** @var CommandBus $bus */
        $bus = $this->kernel->container()->get(CommandBus::class);
        
        $bus->handle(
            new Initialize2Fa(
                1,
                'foo',
                BackupCodes::generate())
        );
        
        $user = new WP_User(1);
        
        $result = wp_signon(['user_login' => $user->user_login, 'user_password'=> 'password']);
        
        $this->assertEquals($user, $result);
    }
    
    /**
     * @test
     */
    public function that_nothing_happens_for_a_user_with_completed_2fa_setup_but_invalid_credentials_passed_to_wp_signon() :void
    {
        $this->kernel->boot();
    
        $this->userHasTwo2FaCompleted(1);
    
        $user = new WP_User(1);
    
        $result = wp_signon(['user_login' => $user->user_login, 'user_password'=> 'bogus']);
    
        $this->assertInstanceOf(WP_Error::class, $result);
    }
    
    /**
     * @test
     */
    public function that_a_user_with_2fa_completed_is_redirected_and_php_is_shut_down() :void
    {
        $this->kernel->boot();
    
        /** @var TestableEventDispatcher $testable_dispatcher */
        $testable_dispatcher = $this->kernel->container()->get(EventDispatcher::class);
        
        $this->userHasTwo2FaCompleted(1);
    
        $user = new WP_User(1);
    
        $testable_dispatcher->listen(function (WPSignonLogin2FaChallengeRedirect $event) {
            $event->do_redirect = false;
        });
        
        wp_signon(['user_login' => $user->user_login, 'user_password'=> 'password']);
        
        $testable_dispatcher->assertDispatched(WPSignonLogin2FaChallengeRedirect::class);
    }
    
    protected function fixturesDir() :string
    {
        return dirname(__DIR__,2 ).'/fixtures/test-app';
    }
    
    /**
     * @param  positive-int  $user_id
     */
    private function userHasTwo2FaCompleted(int $user_id) :void
    {
        /** @var CommandBus $bus */
        $bus = $this->kernel->container()->get(CommandBus::class);
    
        $google_2fa = new Google2FA();
        /** @var non-empty-string $secret */
        $secret = $google_2fa->generateSecretKey();
    
        $bus->handle(
            new Initialize2Fa(
                $user_id,
                $secret,
                BackupCodes::generate())
        );
        $otp = $google_2fa->getCurrentOtp($secret);
        $bus->handle(new Complete2FaSetup($user_id, $otp));
    }
    
    
}