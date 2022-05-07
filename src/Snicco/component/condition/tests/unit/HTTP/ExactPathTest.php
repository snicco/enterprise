<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\ExactPath;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class ExactPathTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new ExactPath('/foo');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo',
        ])));

        $condition = new ExactPath('foo');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo',
        ])));

        $condition = new ExactPath('/foo');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => 'foo',
        ])));

        $condition = new ExactPath('foo');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => 'foo',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new ExactPath('/foo');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/bar',
        ])));

        $condition = new ExactPath('/foo/');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo',
        ])));

        $condition = new ExactPath('/bar/');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/bar',
        ])));
    }
}
