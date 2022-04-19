<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Bundle\ApplicationLayer\Tests\wpunit\Command;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Bundle\ApplicationLayer\Command\FakeCommandBus;
use Snicco\Enterprise\Bundle\ApplicationLayer\Tests\fixtures\Command\RentMovieCommand;

/**
 * @internal
 */
final class FakeCommandBusTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_commands_do_nothing(): void
    {
        $bus = new FakeCommandBus();

        $this->assertCount(0, $bus->commands);

        $bus->handle($command = new RentMovieCommand());

        $this->assertCount(1, $bus->commands);
        $this->assertTrue(isset($bus->commands[0]));
        $this->assertSame($command, $bus->commands[0]);
    }
}
