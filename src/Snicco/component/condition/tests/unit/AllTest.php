<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use RuntimeException;
use Snicco\Enterprise\Component\Condition\All;
use Snicco\Enterprise\Component\Condition\CallableCondition;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

use const PHP_VERSION;

/**
 * @internal
 */
final class AllTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new All([
            new CallableCondition(fn (): bool => true),
            new CallableCondition(fn (): bool => true),
        ]);

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new All([
            new CallableCondition(fn (): bool => false),
            new CallableCondition(function (): void {
                throw new RuntimeException('This should not be called');
            }),
        ]);

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
    
}
