<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;

use Snicco\Enterprise\Component\Condition\WP\AdminAjaxActionStartsWith;

use Snicco\Enterprise\Component\Condition\WP\IsAjaxAdminAjaxAction;

use function add_filter;

/**
 * @internal
 */
final class IsAdminAjaxActionStartsWithTest extends WPTestCase
{
    use CreateContext;

    protected function setUp(): void
    {
        parent::setUp();
        add_filter('wp_doing_ajax', fn (): bool => true);
    }

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new AdminAjaxActionStartsWith('foo');
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'action' => 'foo',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [
            'action' => 'foobar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'action' => 'bogus',
        ], [
            'action' => 'foo',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([], [
            'action' => 'foobar',
        ], [
            'action' => 'foobar',
        ], )));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new AdminAjaxActionStartsWith('foo');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'action' => 'fo',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [
            'action' => 'bogus',
        ])));

        add_filter('wp_doing_ajax', fn (): bool => false);

        $condition = new IsAjaxAdminAjaxAction('foobar');
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'action' => 'foobar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [
            'action' => 'foobar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'action' => 'bogus',
        ], [
            'action' => 'foobar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([], [
            'action' => 'foobar',
        ], [
            'action' => 'bogus',
        ], )));
    }
}
