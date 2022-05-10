<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsPostEdit;

/**
 * @internal
 */
final class IsPostEditTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new IsPostEdit();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/post.php',
        ])));
    }

    /**
     * @test
     */
    public function that_is_fails(): void
    {
        $condition = new IsPostEdit();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/edit.php',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/post.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsPostEdit());
    }
}
