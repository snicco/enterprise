<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\integration\Auth;

use WP_User;
use WP_Error;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Component\Kernel\Kernel;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\CommandBus;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\BackupCodes;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeRepository;
use Snicco\Enterprise\AuthBundle\Auth\Event\WPAuthenticate2FaChallengeRedirect;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Domain\TwoFactorChallengeValidator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\TwoFactorChallengeGenerator;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Application\Initialize2Fa\Initialize2Fa;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Application\Complete2Fa\Complete2FaSetup;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\TwoFactorSettingsBetterWPDB;
use Snicco\Enterprise\AuthBundle\Auth\TwoFactor\Infrastructure\TwoFactorChallengeRepositoryBetterWPDB;

use function end;
use function dirname;
use function explode;
use function wp_signon;

final class TwoFactorChallenges_WP_signon_Test extends WPTestCase
{
    use BundleTestHelpers;
    
    private Kernel $kernel;
    
    private DIContainer $container;
    
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
            $c->set('snicco_auth.authentication.table_names.2fa_challenges', 'auth_2fa_challenges');
        });
        $this->container = $this->kernel->container();
        parent::setUp();
        (new TwoFactorSettingsBetterWPDB(BetterWPDB::fromWpdb(), 'wp_auth_2fa_settings'))->createTable();
        TwoFactorChallengeRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'wp_auth_2fa_challenges' );
    }
    
    protected function tearDown() :void
    {
        $this->bundle_test->tearDownDirectories();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_auth_2fa_settings');
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_auth_2fa_challenges');
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
        $testable_dispatcher = $this->container->get(EventDispatcher::class);
        
        $this->userHasTwo2FaCompleted(1);
    
        $user = new WP_User(1);
    
        $redirect_url = null;
        
        $testable_dispatcher->listen(function (WPAuthenticate2FaChallengeRedirect $event) use (&$redirect_url){
            $event->do_shutdown = false;
            $redirect_url = $event->redirect_url;
        });
        
        wp_signon(['user_login' => $user->user_login, 'user_password'=> 'password']);
        
        $this->assertIsString($redirect_url);
        
        $parts = explode('/', $redirect_url);
        $token = end($parts);
       
        /** @var TwoFactorChallengeRepository $challenges */
        $challenges = $this->container->get(TwoFactorChallengeRepository::class);
        /** @var TwoFactorChallengeValidator $challenge_validator */
        $challenge_validator = $this->container->get(TwoFactorChallengeValidator::class);
        /** @var TwoFactorChallengeGenerator $challenge_generator */
        $challenge_generator = $this->container->get(TwoFactorChallengeGenerator::class);
        
        [$selector, $plain_validator] = $challenge_generator->splitUserToken($token);
        
        $challenge = $challenges->get($selector);
        
        $this->assertTrue($challenge_validator->isValid(
            $plain_validator,
            $challenge
        ));
        
        $this->assertSame(1, $challenge->user_id);
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