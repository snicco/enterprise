<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Context;
use WP_User;

/**
 * @internal
 */
final class ContextTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_a_null_user_is_returned_if_not_passed(): void
    {
        $context = new Context([], [], [], []);
        $this->assertEquals(new WP_User(0), $context->user());
    }
}
