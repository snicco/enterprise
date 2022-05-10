<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\All;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\FalseCondition;
use Snicco\Enterprise\Component\Condition\Tests\fixtures\TrueCondition;

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
        $condition = new All([new TrueCondition(), new TrueCondition()]);

        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new All([new TrueCondition(), new FalseCondition()]);

        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
