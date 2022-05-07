<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Condition\Tests\wpunit\WP;

use Codeception\TestCase\WPTestCase;
use Snicco\Enterprise\Component\Condition\Tests\CreateContext;
use Snicco\Enterprise\Component\Condition\WP\IsAdminAjax;

use function add_filter;

/**
 * @internal
 */
final class IsAdminAjaxTest extends WPTestCase
{
    use CreateContext;

    /**
     * @test
     */
    public function that_it_passes(): void
    {
        add_filter('wp_doing_ajax', fn (): bool => true);

        $condition = new IsAdminAjax();
        $this->assertTrue($condition->isTruthy($this->createContext()));
    }

    /**
     * @test
     */
    public function that_it_fails(): void
    {
        add_filter('wp_doing_ajax', fn (): bool => false);

        $condition = new IsAdminAjax();
        $this->assertFalse($condition->isTruthy($this->createContext()));
    }
}
