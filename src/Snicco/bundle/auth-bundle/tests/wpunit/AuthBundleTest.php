<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit;

use RuntimeException;
use WP_Session_Tokens;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Enterprise\Bundle\Auth\WPAuthSessions;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Enterprise\Bundle\Auth\SessionRepository;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasIdle;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Enterprise\Bundle\Auth\Event\SessionWasRotated;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Event\SessionActivityRecorded;
use WP_User;

use Snicco\Enterprise\Bundle\Auth\Event\SessionRotationIntervalExceeded;

use function hash;
use function sleep;
use function dirname;
use function sprintf;
use function do_action;
use function time;
use function add_filter;
use function add_action;

use const LOGGED_IN_COOKIE;

/**
 * @internal
 */
final class AuthBundleTest extends WPTestCase
{
    
    use BundleTestHelpers;
    
    /**
     * @var string
     */
    private const TABLE_NAME = 'wp_snicco_auth_sessions';
    
    private SessionRepository $session_repository;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        SessionRepository::createTable(self::TABLE_NAME);
    }
    
    protected function tearDown() :void
    {
        $this->bundle_test->tearDownDirectories();
        /** @psalm-suppress ArgumentTypeCoercion */
        BetterWPDB::fromWpdb()->unprepared(sprintf('DROP TABLE IF EXISTS %s', self::TABLE_NAME));
        parent::tearDown();
    }
    
    /**
     * @test
     */
    public function that_the_bundle_is_used() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $this->assertTrue($kernel->usesBundle('snicco/auth-bundle'));
    }
    
    /**
     * @test
     */
    public function that_the_auth_cookie_valid_hook_is_mapped() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $kernel->container()
                             ->get(TestableEventDispatcher::class);
        
        $dispatcher->assertNotDispatched(SessionActivityRecorded::class);
        
        do_action('auth_cookie_valid', [
            'token' => 'foobar',
        ], new WP_User(1));
        
        $dispatcher->assertDispatched(
            fn(SessionActivityRecorded $event) :bool => 1 === $event->user_id
                                                        && 'foobar' === $event->raw_token
                                                        && time() === $event->timestamp
        );
    }
    
    /**
     * @test
     */
    public function that_the_session_token_class_is_set() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $sessions = WP_Session_Tokens::get_instance(1);
        $this->assertInstanceOf(WPAuthSessions::class, $sessions);
        
        $this->assertSame([], $sessions->get_all());
    }
    
    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_another_custom_session_class_is_used() :void
    {
        add_filter('session_token_manager', fn() => 'foobar');
        
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'snicco/auth-bundle uses a custom session token implementation but there is already another one [foobar] hooked to the "session_token_manager" filer.'
        );
        
        WP_Session_Tokens::get_instance(1);
    }
    
    /**
     * @test
     */
    public function that_session_activity_is_updated_on_valid_auth_cookie_hook() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $container = $kernel->container();
        
        /** @var SessionRepository $session_repo */
        $session_repo = $container->get(SessionRepository::class);
        
        $session_repo->update(1, $token = (string)hash('sha256', 'foobar'), ['expiration' => time() + 10]);
        
        $db = BetterWPDB::fromWpdb();
        
        $last = $db->selectValue('select last_activity from '.self::TABLE_NAME.' where hashed_token = ?', [$token]);
        $this->assertSame(time(), $last);
        
        sleep(2);
        
        do_action('auth_cookie_valid', ['token' => 'foobar'], new WP_User(1));
        
        $last =
            (int)$db->selectValue('select last_activity from '.self::TABLE_NAME.' where hashed_token = ?', [$token]);
        $this->assertSame(time(), $last);
    }
    
    /**
     * @test
     */
    public function that_sessions_are_rotated() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('snicco.auth.rotation_interval', 10);
            $config->set('snicco.auth.idle_timeout', 1000);
        });
        
        $kernel->boot();
        
        $container = $kernel->container();
        
        $token = (string)hash('sha256', 'foobar');
        
        /** @var SessionRepository $session_repo */
        $session_repo = $container->get(SessionRepository::class);
        $session_repo->update(1, $token, ['expiration' => time() + 10]);
        
        /** @var EventDispatcher $events */
        $events = $container->get(EventDispatcher::class);
        
        $new_token_hashed = null;
        $new_token_raw = null;
        $events->listen(
            SessionWasRotated::class,
            function (SessionWasRotated $event) use (&$new_token_hashed, &$new_token_raw) {
                $new_token_hashed = $event->newTokenHashed();
                $new_token_raw = $event->newTokenRaw();
            });
        
        $events->dispatch(new SessionRotationIntervalExceeded($token, 1));
    
        $this->assertIsString($new_token_hashed);
        $this->assertIsString($new_token_raw);
        
        $db = BetterWPDB::fromWpdb();
        
        $last = $db->selectValue(
            'select next_rotation_at from '.self::TABLE_NAME.' where hashed_token = ?',
            [$new_token_hashed]
        );
        $this->assertSame(time() + 10, $last);
        
        $this->expectException(NoMatchingRowFound::class);
        $db->selectValue(
            'select next_rotation_at from '.self::TABLE_NAME.' where hashed_token = ?',
            [$token]
        );
        
        $this->assertTrue(isset($_COOKIE[LOGGED_IN_COOKIE]), 'Logged in cookie not updated.');
        $this->assertSame($new_token_raw, $_COOKIE[LOGGED_IN_COOKIE]);
    }
    
    protected function fixturesDir() :string
    {
        return dirname(__DIR__).'/fixtures';
    }
    
}
