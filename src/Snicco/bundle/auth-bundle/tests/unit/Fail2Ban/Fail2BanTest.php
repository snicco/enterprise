<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\unit\Fail2Ban;

use Codeception\Test\Unit;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Core\BannableEvent;
use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Core\Fail2Ban;

use Snicco\Enterprise\Bundle\Auth\Fail2Ban\Core\Fail2BanEntry;
use Snicco\Enterprise\Bundle\Auth\Tests\fixtures\TestSysLogger;

use const LOG_AUTH;
use const LOG_NOTICE;
use const LOG_PID;
use const LOG_WARNING;

/**
 * @internal
 */
final class Fail2BanTest extends Unit
{
    /**
     * @test
     */
    public function that_an_entry_can_be_reported(): void
    {
        $fail2ban = new Fail2Ban(
            $test_logger = new TestSysLogger(),
            [
                'daemon' => 'snicco',
                'flags' => LOG_PID,
                'facility' => LOG_AUTH,
            ]
        );

        $fail2ban->report(new TestBannable('foo'));

        $test_logger->assertLogOpened();
        $test_logger->assertLogOpenedWithFlags(LOG_PID);
        $test_logger->assertLogOpenedForFacility(LOG_AUTH);

        $test_logger->assertLogEntry(LOG_WARNING, 'foo from 127.0.0.1');
    }

    /**
     * @test
     */
    public function that_the_fail2_ban_entry_class_works(): void
    {
        $fail2ban = new Fail2Ban(
            $test_logger = new TestSysLogger(),
            [
                'daemon' => 'snicco',
                'flags' => LOG_PID,
                'facility' => LOG_AUTH,
            ]
        );

        unset($_SERVER['REMOTE_ADDR']);

        $fail2ban->report(new Fail2BanEntry('foobar', LOG_WARNING));

        $test_logger->assertLogEntry(LOG_WARNING, 'foobar from ');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $fail2ban->report(new Fail2BanEntry('foobaz', LOG_NOTICE));
        $test_logger->assertLogEntry(LOG_NOTICE, 'foobaz from 127.0.0.1');

        $fail2ban->report(new Fail2BanEntry('foobam', LOG_NOTICE, '127.1.1.1'));
        $test_logger->assertLogEntry(LOG_NOTICE, 'foobam from 127.1.1.1');
    }
}

final class TestBannable implements BannableEvent
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function priority(): int
    {
        return LOG_WARNING;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function ip(): string
    {
        return '127.0.0.1';
    }
}
