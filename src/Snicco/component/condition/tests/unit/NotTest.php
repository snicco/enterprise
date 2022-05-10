<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Not;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\FalseCondition;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\TrueCondition;

/**
 * @internal
 */
final class NotTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new Not(new FalseCondition());

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new Not(new TrueCondition());

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
