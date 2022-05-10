<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
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

        $condition = new AdminPageStartsWith(['foo', 'bogus']);
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'bogusbar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'bogus-bar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'page' => 'bogus',
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

        $condition = new AdminPageStartsWith(['foo', 'bogus']);
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'whateverbar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'whatever-bar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => 'whatever',
        ])));

        $condition = new AdminPageStartsWith('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => ['foo'],
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'page' => '',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new AdminPageStartsWith('foo'));
        Assert::canBeNormalized(new AdminPageStartsWith(['foo', 'bar']));
    }
}
