<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Any;
use Snicco\Enterprise\Component\Condition\CallableCondition;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class AnyTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new Any([
            new CallableCondition(fn (): bool => false),
            new CallableCondition(fn (): bool => true),
        ]);

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new Any([
            new CallableCondition(fn (): bool => false),
            new CallableCondition(fn (): bool => false),
        ]);

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
