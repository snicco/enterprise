<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\UserRole;
use WP_User;

/**
 * @internal
 */
final class UserRoleTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new UserRole('administrator');
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [], [], new WP_User(1))));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new UserRole('editor');
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [], new WP_User(1))));
    }
}
