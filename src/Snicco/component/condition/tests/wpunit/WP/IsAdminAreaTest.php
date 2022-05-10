<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\Tests\WPContext;
use Snicco\Enterprise\Component\Condition\WP\IsAdminArea;

/**
 * @internal
 */
final class IsAdminAreaTest extends WPTestCase
{
    use CreateContext;

    protected function setUp(): void
    {
        parent::setUp();
        WPContext::resetAll();
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
        WPContext::forceIsAdmin();
        $condition = new IsAdminArea();
        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        WPContext::forceIsAdmin(false);
        $condition = new IsAdminArea();
        $this->assertFalse($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsAdminArea());
    }
}
