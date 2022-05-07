<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\unit\WP;

use Codeception\Test\Unit;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsAdminEntryPoint;

/**
 * @internal
 */
final class IsAdminEntryPointTest extends Unit
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new IsAdminEntryPoint(['themes.php', 'edit.php']);

        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/themes.php',
        ])));

        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/edit.php',
        ])));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new IsAdminEntryPoint(['themes.php', 'edit.php']);

        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/foo.php',
        ])));

        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/edit.php',
        ])));
    }
}
