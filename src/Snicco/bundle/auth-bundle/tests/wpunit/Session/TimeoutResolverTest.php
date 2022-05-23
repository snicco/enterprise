<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\Auth\Tests\wpunit\Session;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Enterprise\Bundle\Auth\Session\Core\TimeoutResolver;

/**
 * @internal
 */
final class TimeoutResolverTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_timeouts_are_correct(): void
    {
        $resolver = new TimeoutResolver(10, 10, $clock = new TestClock());

        $now = $clock->currentTimestamp();

        $this->assertSame(TimeoutResolver::NOT_IDLE, $resolver->idleStatus($now, 1));
        $this->assertFalse($resolver->needsRotation($now, 1));

        $clock->travelIntoFuture(10);

        $this->assertSame(TimeoutResolver::NOT_IDLE, $resolver->idleStatus($now, 1));
        $this->assertFalse($resolver->needsRotation($now, 1));

        $clock->travelIntoFuture(1);

        $this->assertSame(TimeoutResolver::TOTALLY_IDLE, $resolver->idleStatus($now, 1));
        $this->assertTrue($resolver->needsRotation($now, 1));
    }

    /**
     * @test
     */
    public function that_users_can_be_allowed_a_weekly_idle_timeout(): void
    {
        $resolver = new TimeoutResolver(10, 10, $clock = new TestClock(), true);

        $now = $clock->currentTimestamp();
        $clock->travelIntoFuture(11);

        $this->assertSame(TimeoutResolver::WEEKLY_IDLE, $resolver->idleStatus($now, 1));
    }
}
