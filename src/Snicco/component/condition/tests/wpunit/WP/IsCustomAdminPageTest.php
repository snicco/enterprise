<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsCustomAdminPage;

/**
 * @internal
 */
final class IsCustomAdminPageTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_is_passes(): void
    {
        $condition = new IsCustomAdminPage();

        $this->assertTrue($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/admin.php',
        ])));
    }

    /**
     * @test
     */
    public function that_is_fails(): void
    {
        $condition = new IsCustomAdminPage();

        $this->assertFalse($condition->isTruthy($this->createContext([
            'SCRIPT_NAME' => '/wp-admin/users.php',
        ])));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new IsCustomAdminPage());
    }
}
