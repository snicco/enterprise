<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\integration\Session\Infrastructure;

use RuntimeException;
use WP_Session_Tokens;
use Snicco\Component\Kernel\Kernel;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Auth\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Auth\Session\Infrastructure\WPAuthSessionTokens;

use Snicco\Enterprise\Bundle\Auth\Session\Infrastructure\BetterWPDBSessionRepository;

use function time;
use function hash;
use function sleep;
use function dirname;
use function wp_generate_auth_cookie;
use function wp_validate_auth_cookie;

final class SessionModuleTest extends WPTestCase
{
    use BundleTestHelpers;
    
    private Kernel $kernel;
    
    protected function setUp(): void
    {
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        parent::setUp();
        BetterWPDBSessionRepository::createTable('wp_snicco_auth_sessions');
    }
    
    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        BetterWPDB::fromWpdb()->unprepared("DROP TABLE IF EXISTS wp_snicco_auth_sessions");
        parent::tearDown();
    }
    
    protected function fixturesDir() :string
    {
        return dirname(__DIR__, 3).'/fixtures/test-app';
    }
    
    /**
     * @test
     */
    public function that_the_session_token_class_is_set(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $sessions = WP_Session_Tokens::get_instance(1);
        $this->assertInstanceOf(WPAuthSessionTokens::class, $sessions);
        
        $this->assertSame([], $sessions->get_all());
    }
    
    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_another_custom_session_class_is_used(): void
    {
        add_filter('session_token_manager', fn (): string => 'foobar');
        
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
    public function that_session_activity_is_updated_on_valid_auth_cookie_hook(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        
        $kernel->boot();
        
        $container = $kernel->container();
        
        /** @var SessionManager $session_manager */
        $session_manager = $container->get(SessionManager::class);
        
        $session_manager->save(new AuthSession(
            $hashed_token = (string) hash('sha256', 'foobar'),
            1,
            time(),
            time(),
            ['expiration' => time()+1000]
        ));
    
        sleep(1);
    
        $cookie = wp_generate_auth_cookie(1, time() +1000, 'auth', 'foobar');
        
        wp_validate_auth_cookie($cookie, 'auth');
        
        $session = $session_manager->getSession($hashed_token);
        
        $this->assertSame(time(), $session->lastActivity());
    }
    
}