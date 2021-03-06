<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\CallableCondition;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Unless;

/**
 * @internal
 */
final class UnlessTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new Unless(new CallableCondition(fn (): bool => false));

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new Unless(new CallableCondition(fn (): bool => true));

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
