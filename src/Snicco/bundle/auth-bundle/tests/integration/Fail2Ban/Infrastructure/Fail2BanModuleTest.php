<?php

declare(strict_types=1);

namespace Snicco\Enterprise\AuthBundle\Tests\integration\Fail2Ban\Infrastructure;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Enterprise\AuthBundle\Fail2Ban\Domain\Syslogger;
use Snicco\Enterprise\AuthBundle\Fail2Ban\Infrastructure\BanworthyEvent;
use Snicco\Enterprise\AuthBundle\Tests\fixtures\TestSysLogger;

use function dirname;
use function time;
use function wp_authenticate;
use function wp_generate_auth_cookie;
use function wp_validate_auth_cookie;
use const LOG_AUTH;
use const LOG_ERR;
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
    public function that_all_events_implementing_the_banworthy_interface_a_reported(): void
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
            $event1 = new Banworthy1()
        );
        $event_dispatcher->dispatch(
            $event2 = new Banworthy2()
        );

        $test_syslogger->assertLogOpenedForFacility(LOG_AUTH);
        $test_syslogger->assertLogOpenedWithFlags(LOG_PID);
        $test_syslogger->assertLogOpenedWithPrefix('snicco_auth');

        $test_syslogger->assertLogEntry(LOG_WARNING, $event1->message() . ' from ' . (string) $event1->ip());
        $test_syslogger->assertLogEntry(LOG_ERR, $event2->message() . ' from ' . (string) $event2->ip());
    }

    /**
     * @test
     */
    public function that_wp_login_failed_action_is_reported(): void
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
    public function that_the_bad_auth_cookie_hash_action_is_reported(): void
    {
        $test_syslogger = new TestSysLogger();

        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('snicco_auth.modules', [
                'fail2ban',
            ]);
        });

        $this->kernel->afterRegister(function (Kernel $kernel) use ($test_syslogger): void {
            $kernel->container()
                ->instance(Syslogger::class, $test_syslogger);
        });

        $this->kernel->boot();

        $cookie = wp_generate_auth_cookie(1, time() + 10);

        wp_validate_auth_cookie($cookie, 'auth');

        $test_syslogger->assertNothingLogged();

        wp_validate_auth_cookie($cookie . 'a', 'auth');

        $test_syslogger->assertLogOpenedForFacility(LOG_AUTH);
        $test_syslogger->assertLogOpenedWithFlags(LOG_PID);
        $test_syslogger->assertLogOpenedWithPrefix('snicco_auth');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $test_syslogger->assertLogEntry(LOG_WARNING, 'Tampered auth cookie provided from 127.0.0.1');
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__, 3) . '/fixtures/test-app';
    }
}

final class Banworthy1 implements BanworthyEvent
{
    public function priority(): int
    {
        return LOG_WARNING;
    }

    public function message(): string
    {
        return 'ban-1';
    }

    public function ip(): ?string
    {
        return '127.0.0.1';
    }
}

final class Banworthy2 implements BanworthyEvent
{
    public function priority(): int
    {
        return LOG_ERR;
    }

    public function message(): string
    {
        return 'ban-2';
    }

    public function ip(): ?string
    {
        return '127.0.0.1';
    }
}
