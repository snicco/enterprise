<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsFrontend;

/**
 * @internal
 */
final class IsFrontendTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new IsFrontend();

        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/index.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsFrontend();

        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/foo.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsFrontend());
    }
}
