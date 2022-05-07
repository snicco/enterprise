<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\WPContext;
use Snicco\Enterprise\Component\Condition\WP\AdminPageStartsWith;

/**
 * @internal
 */
final class AdminPageStartsWithTest extends WPTestCase
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
        $condition = new AdminPageStartsWith('foo');
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'foobar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'foo-bar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'foo',
        ])));
    }

    /**
     * @test
     */
    public function test_it_fails(): void
    {
        $condition = new AdminPageStartsWith('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'biz',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'fo-bar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => ['foo'],
        ])));

        WPContext::forceIsAdmin(false);

        $condition = new AdminPageStartsWith('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'foobar',
        ])));
    }
}
