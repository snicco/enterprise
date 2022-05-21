<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Fail2Ban;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\FailedMagicLinkAuthentication;
use Snicco\Enterprise\Bundle\Auth\Authentication\Event\FailedPasswordAuthentication;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Syslogger;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\TestSysLogger;

use function dirname;
use function do_action;
use function wp_authenticate;

use const LOG_AUTH;
use const LOG_PID;
use const LOG_WARNING;

/**
 * @internal
 */
final class Fail2BanModuleTest extends WPTestCase
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
    }

    /**
     * @test
     */
    public function that_all_bannable_events_are_reported(): void
    {
        $test_syslogger = new TestSysLogger();

        $this->kernel->afterRegister(function (Kernel $kernel) use ($test_syslogger): void {
            $kernel->container()
                ->instance(Syslogger::class, $test_syslogger);
        });

        $this->kernel->boot();

        /** @var EventDispatcher $event_dispatcher */
        $event_dispatcher = $this->kernel->container()
            ->get(EventDispatcher::class);

        $event_dispatcher->dispatch(
            $event1 = new FailedPasswordAuthentication('127.0.0.1', 'foo')
        );
        $event_dispatcher->dispatch(
            $event2 = new FailedMagicLinkAuthentication('127.0.0.1', 'foo')
        );

        $test_syslogger->assertLogOpenedForFacility(LOG_AUTH);
        $test_syslogger->assertLogOpenedWithFlags(LOG_PID);
        $test_syslogger->assertLogOpenedWithPrefix('snicco_auth');

        $test_syslogger->assertLogEntry(LOG_WARNING, $event1->message() . ' from ' . $event1->ip());
        $test_syslogger->assertLogEntry(LOG_WARNING, $event2->message() . ' from ' . $event2->ip());
    }

    /**
     * @test
     */
    public function that_wp_login_failed_is_reported(): void
    {
        $test_syslogger = new TestSysLogger();

        $this->kernel->afterRegister(function (Kernel $kernel) use ($test_syslogger): void {
            $kernel->container()
                ->instance(Syslogger::class, $test_syslogger);
        });

        $this->kernel->boot();

        wp_authenticate('admin', 'bogus');

        $test_syslogger->assertLogOpenedForFacility(LOG_AUTH);
        $test_syslogger->assertLogOpenedWithFlags(LOG_PID);
        $test_syslogger->assertLogOpenedWithPrefix('snicco_auth');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $test_syslogger->assertLogEntry(LOG_WARNING, 'WordPress login failed for user admin from 127.0.0.1');
    }

    /**
     * @test
     */
    public function that_bad_auth_cookie_hash_is_reported(): void
    {
        $test_syslogger = new TestSysLogger();

        $this->kernel->afterRegister(function (Kernel $kernel) use ($test_syslogger): void {
            $kernel->container()
                ->instance(Syslogger::class, $test_syslogger);
        });

        $this->kernel->boot();

        do_action('auth_cookie_bad_hash');

        $test_syslogger->assertLogOpenedForFacility(LOG_AUTH);
        $test_syslogger->assertLogOpenedWithFlags(LOG_PID);
        $test_syslogger->assertLogOpenedWithPrefix('snicco_auth');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $test_syslogger->assertLogEntry(LOG_WARNING, 'Tampered auth cookie provided from 127.0.0.1');
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 2) . '/fixtures';
    }
}
