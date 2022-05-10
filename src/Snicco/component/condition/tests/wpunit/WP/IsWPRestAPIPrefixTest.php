<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsWPRestAPIPrefix;

/**
 * @internal
 */
final class IsWPRestAPIPrefixTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new IsWPRestAPIPrefix('/snicco/foo');

        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/foo/bar',
        ])));

        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/foo',
        ])));

        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/foo/',
        ])));

        $condition = new IsWPRestAPIPrefix('/snicco/foo/');

        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/foo/bar',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsWPRestAPIPrefix('/snicco/foo');

        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/bogus/foo/bar',
        ])));

        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/bar',
        ])));

        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/snicco/',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsWPRestAPIPrefix('/snicco/foo'));
    }
}
