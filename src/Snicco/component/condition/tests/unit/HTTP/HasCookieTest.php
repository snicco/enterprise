<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\HasCookie;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class HasCookieTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new HasCookie('foo', 'bar');
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [], [
            'foo' => 'bar',
        ])));

        $condition = new HasCookie('foo', null);
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [], [
            'foo' => 'baz',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [], [
            'foo' => 'whatever',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new HasCookie('foo', 'bar');
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [
            'foo' => 'baz',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [
            'foo' => ['bar'],
        ])));

        $condition = new HasCookie('foo', null);
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [])));
    }
}
