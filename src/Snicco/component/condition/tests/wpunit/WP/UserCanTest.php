<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\Assert;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\UserCan;
use WP_User;

/**
 * @internal
 */
final class UserCanTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        $condition = new UserCan('manage_options');
        $this->assertTrue($condition->isTruthy($this->createContext([], [], [], [], new WP_User(1))));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        $condition = new UserCan('some_other_cap');
        $this->assertFalse($condition->isTruthy($this->createContext([], [], [], [], new WP_User(1))));
    }

    /**
     * @test
     */
    public function that_json_serialize_works(): void
    {
        Assert::canBeNormalized(new UserCan('foo'));
    }
}
