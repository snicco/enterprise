<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsWPRestAPI;

/**
 * @internal
 */
final class IsWPRestAPITest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsWPRestAPI();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/foo/bar',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json/',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-json',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsWPRestAPI();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-bogus/foo/bar',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-bogus/',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'REQUEST_URI' => '/wp-bogus',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsWPRestAPI());
    }
}
