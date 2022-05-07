<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\HTTP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\HTTP\RegexPath;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

/**
 * @internal
 */
final class RegexPathTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new RegexPath('[abc]\d{2,3}');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/a11',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/c111',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => 'b22',
        ])));

        $condition = new RegexPath('\/[abc]\d{2,3}');
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/a11',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/c111',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => 'b22',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new RegexPath('[abc]\d{2,3}');
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/1aa1',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/d22',
        ])));
    }
}
