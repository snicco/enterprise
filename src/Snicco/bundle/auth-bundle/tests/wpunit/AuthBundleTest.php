<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Enterprise\Bundle\Auth\AuthBundle;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Session\Core\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Core\Event\SessionActivityRecorded;
use Snicco\Enterprise\Bundle\Auth\Session\Core\SessionRepository;
use Snicco\Enterprise\Bundle\Auth\Session\Core\WPAuthSessions;
use WP_Session_Tokens;
use WP_User;

use function add_filter;
use function dirname;
use function do_action;
use function sleep;
use function sprintf;
use function time;

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
    public function that_an_exception_is_thrown_if_the_http_routing_bundle_is_not_used() :void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('kernel.bundles', [
                Environment::ALL => [
                    AuthBundle::class,
                    BetterWPHooksBundle::class,
                    BetterWPDBBundle::class,
                ],
            ]);
        });
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('snicco/http-routing-bundle');
        
        $kernel->boot();
        
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
        add_filter('session_token_manager', fn() :string => 'foobar');
        
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
        
        $foo_token = $session_repo->hashToken('foo');
        
        $session = new AuthSession($foo_token, 1, [
            'expiration' => time() + 10,
        ]);
        
        $session_repo->save($session);
        
        $db = BetterWPDB::fromWpdb();
        
        $last = $db->selectValue(
            'select last_activity from '.self::TABLE_NAME.' where hashed_token = ?',
            [$foo_token]
        );
        $this->assertSame(time(), $last);
        
        sleep(2);
        
        do_action('auth_cookie_valid', [
            'token' => 'foo',
        ], new WP_User(1));
        
        $last =
            (int)$db->selectValue(
                'select last_activity from '.self::TABLE_NAME.' where hashed_token = ?',
                [$foo_token]
            );
        $this->assertSame(time(), $last);
    }
    
    protected function fixturesDir() :string
    {
        return dirname(__DIR__).'/fixtures';
    }
    
}
