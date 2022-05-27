<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Fortress\Tests\integration\Session\Infrastructure;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\AuthSession;
use Snicco\Enterprise\Bundle\Fortress\Session\Domain\SessionManager;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\SessionRepositoryBetterWPDB;
use Snicco\Enterprise\Bundle\Fortress\Session\Infrastructure\WPAuthSessionTokens;
use WP_Session_Tokens;

use function add_filter;
use function dirname;
use function hash;
use function sleep;
use function time;
use function wp_generate_auth_cookie;
use function wp_validate_auth_cookie;

/**
 * @internal
 */
final class SessionModuleTest extends WPTestCase
{
    use BundleTestHelpers;

    protected function setUp(): void
    {
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        parent::setUp();
        SessionRepositoryBetterWPDB::createTable(BetterWPDB::fromWpdb(), 'wp_snicco_auth_sessions');
    }

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS wp_snicco_auth_sessions');
        parent::tearDown();
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
            [
                'expiration' => time() + 1000,
            ]
        ));

        sleep(1);

        $cookie = wp_generate_auth_cookie(1, time() + 1000, 'auth', 'foobar');

        wp_validate_auth_cookie($cookie, 'auth');

        $session = $session_manager->getSession($hashed_token);

        $this->assertSame(time(), $session->lastActivity());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 3) . '/fixtures/test-app';
    }
}
