<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsNewPost;

/**
 * @internal
 */
final class IsNewPostTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsNewPost();
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/post-new.php',
        ])));
        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-admin/post-new.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsNewPost();
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-foo/post-new.php',
        ])));
        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/blog/wp-foo/post-new.php',
        ])));
    }
}
