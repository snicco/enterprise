<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Tests\wpunit;

use Codeception\TestCase\WPTestCase;

use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Component\Condition\Context;

use Snicco\Enterprise\Component\Performance\Core\Event\DisablingTheme;

use Snicco\Enterprise\Component\Performance\Core\Tests\AlwaysFalseCondition;

use Snicco\Enterprise\Component\Performance\Core\ThemeDisabler;

use function add_filter;
use function dirname;

use function get_stylesheet_directory;

use function get_template_directory;

/**
 * @internal
 */
final class ThemeDisablerTest extends WPTestCase
{
    /**
     * @test
     */
    public function that_the_theme_can_be_disabled(): void
    {
        $theme_disabler = $this->themeDisabler();

        $theme_disabler->applyRules();

        $this->assertIsCustomTheme();
    }

    /**
     * @test
     */
    public function that_the_theme_is_only_disabled_if_the_condition_is_truthy(): void
    {
        $theme_disabler = $this->themeDisabler();
        $theme_disabler->applyRules(new AlwaysFalseCondition());

        $this->assertIsNotCustomTheme();
    }

    /**
     * @test
     */
    public function that_an_event_is_dispatched_after_the_theme_was_disabled(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $theme_disabler = $this->themeDisabler($dispatcher);

        $theme_disabler->applyRules();

        $this->assertIsCustomTheme();
        $dispatcher->assertDispatchedTimes('snicco/performance:disabled_theme');
    }

    /**
     * @test
     */
    public function that_disabling_the_theme_can_be_prevented_with_wordpress_filters(): void
    {
        $dispatcher = new TestableEventDispatcher(new WPEventDispatcher(new BaseEventDispatcher()));
        $theme_disabler = $this->themeDisabler($dispatcher);

        add_filter('snicco/performance:disabling_theme', function (DisablingTheme $disabling_theme): void {
            $this->assertInstanceOf(Context::class, $disabling_theme->context());
            $disabling_theme->proceed = false;
        });

        $theme_disabler->applyRules();

        $this->assertIsNotCustomTheme();
        $dispatcher->assertNotDispatched('snicco/performance:theme_disabled');
    }

    private function themeDisabler(EventDispatcher $dispatcher = null): ThemeDisabler
    {
        $dispatcher = $dispatcher ?: new BaseEventDispatcher();

        return new ThemeDisabler($dispatcher, new Context([], [], [], [],));
    }

    private function assertIsCustomTheme(): void
    {
        $this->assertSame(dirname(__DIR__, 2) . '/resources/themes/snicco-performance-theme', get_template_directory());
        $this->assertSame(
            dirname(__DIR__, 2) . '/resources/themes/snicco-performance-theme',
            get_stylesheet_directory()
        );
    }

    private function assertIsNotCustomTheme(): void
    {
        $this->assertNotSame(
            dirname(__DIR__, 2) . '/resources/themes/snicco-performance-theme',
            get_template_directory()
        );
        $this->assertNotSame(
            dirname(__DIR__, 2) . '/resources/themes/snicco-performance-theme',
            get_stylesheet_directory()
        );
    }
}
