<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\WPContext;
use Snicco\Enterprise\Component\Condition\WP\AdminPage;

/**
 * @internal
 */
final class AdminPageTest extends WPTestCase
{
    use CreateContext;

    protected function setUp(): void
    {
        parent::setUp();
        WPContext::resetAll();
        WPContext::forceIsAdmin();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        WPContext::resetAll();
    }

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new AdminPage('foo');
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'foo',
        ])));

        $condition = new AdminPage(['foo', 'bar']);
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'foo',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'bar',
        ])));
    }

    /**
     * @test
     */
    public function test_it_fails(): void
    {
        $condition = new AdminPage('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'bar',
        ])));

        $condition = new AdminPage(['foo', 'bar']);
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'baz',
        ])));

        $condition = new AdminPage('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => ['foo'],
        ])));

        WPContext::forceIsAdmin(false);

        $condition = new AdminPage('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'foo',
        ])));
    }
}
