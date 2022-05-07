<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\PathSegment;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class PathSegmentTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new PathSegment('/foo');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('foo/bar');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('foo/bar/baz');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('/baz');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new PathSegment('/boo');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('foo/baz');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('foo/baz/bar');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));

        $condition = new PathSegment('/baz/');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/foo/bar/baz',
        ])));
    }
}
