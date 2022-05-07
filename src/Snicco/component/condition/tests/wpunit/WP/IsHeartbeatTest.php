<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsHeartbeat;

/**
 * @internal
 */
final class IsHeartbeatTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsHeartbeat();
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [
            'action' => 'heartbeat',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsHeartbeat();
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [
            'action' => 'foo',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [])));
    }
}
