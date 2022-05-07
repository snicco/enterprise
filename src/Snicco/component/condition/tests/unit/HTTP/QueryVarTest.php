<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\QueryVar;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class QueryVarTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new QueryVar([
            'foo' => 'bar',
        ]);
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'foo' => 'bar',
        ])));

        $condition = new QueryVar([
            'foo' => 'bar',
            'baz' => 'biz',
        ]);
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'baz' => 'biz',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new QueryVar([
            'foo' => 'bar',
        ]);
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'foo' => 'biz',
        ])));

        $condition = new QueryVar([
            'foo' => 'bar',
            'baz' => 'biz',
        ]);
        $this->assertFalse($condition->isTruthy($this->createContext([], [])));
    }
}
