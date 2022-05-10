<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsPostsList;

/**
 * @internal
 */
final class IsPostsListTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsPostsList();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/edit.php',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-admin/edit.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsPostsList();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-foo/edit.php',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-foo/edit.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsPostsList());
    }
}
