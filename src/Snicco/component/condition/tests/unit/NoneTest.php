<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\None;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\FalseCondition;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\TrueCondition;

/**
 * @internal
 */
final class NoneTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new None([new FalseCondition(), new FalseCondition(), new FalseCondition()]);

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_is_fails(): void
    {
        $condition = new None([new FalseCondition(), new FalseCondition(), new TrueCondition()]);

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
