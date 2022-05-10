<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core\Tests\wpunit;

use Codeception\TestCase\WPTestCase;
use LogicException;
use RuntimeException;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Enterprise\Component\Condition\Context;
use Snicco\Enterprise\Component\Performance\Core\Event\DisablingPlugin;
use Snicco\Enterprise\Component\Performance\Core\Event\PluginWasDisabled;
use Snicco\Enterprise\Component\Performance\Core\PluginDisabler;

use Snicco\Enterprise\Component\Performance\Core\Tests\AlwaysFalseCondition;

use Snicco\Enterprise\Component\Performance\Core\Tests\AlwaysTrueCondition;

use function add_filter;
use function do_action;
use function get_option;
use function remove_all_filters;
use function update_option;
use function wp_cache_flush;

/**
 * @internal
 */
final class PluginDisablerTest extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->activePluginsAre(['hello.php', 'akismet/akismet.php']);
        wp_cache_flush();
    }

    /**
     * @test
     */
    public function that_a_single_plugin_can_be_disabled(): void
    {
        $disabler = $this->pluginDisabler();
        $disabler->disable('hello.php');

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());

        $disabler->applyRules();

        $this->assertSame(['akismet/akismet.php'], $this->fetchActivePlugins());
    }

    /**
     * @test
     */
    public function that_the_active_plugins_are_reset_back_to_normal_on_plugins_loaded(): void
    {
        $disabler = $this->pluginDisabler();
        $disabler->disable('hello.php');

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());

        $disabler->applyRules();

        $this->assertSame(['akismet/akismet.php'], $this->fetchActivePlugins());

        do_action('plugin_loaded');

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());
    }

    /**
     * @test
     */
    public function that_applying_rules_twice_has_no_effect(): void
    {
        $disabler = $this->pluginDisabler();
        $disabler->disable('hello.php');

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());

        $disabler->applyRules();
        $disabler->applyRules();

        $this->assertSame(['akismet/akismet.php'], $this->fetchActivePlugins());
    }

    /**
     * @test
     */
    public function that_multiple_plugins_can_be_disabled(): void
    {
        $disabler = $this->pluginDisabler();
        $disabler->disable(['hello.php', 'akismet/akismet.php']);

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());

        $disabler->applyRules();

        $this->assertSame([], $this->fetchActivePlugins());
    }

    /**
     * @test
     */
    public function that_a_condition_can_be_used_to_remove_plugins(): void
    {
        $disabler = $this->pluginDisabler();
        $disabler->disable('hello.php', new AlwaysTrueCondition());
        $disabler->applyRules();

        $this->assertSame(['akismet/akismet.php'], $this->fetchActivePlugins());

        $this->resetEverything();

        $disabler = $this->pluginDisabler();
        $disabler->disable('hello.php', new AlwaysFalseCondition());
        $disabler->applyRules();

        $this->assertSame(['hello.php', 'akismet/akismet.php'], $this->fetchActivePlugins());
    }

    /**
     * @test
     */
    public function that_all_plugins_for_a_specific_vendor_prefix_can_be_removed(): void
    {
        $this->activePluginsAre(
            ['foo_vendor/foo1.php', 'foo_vendor/foo2.php', 'bar_vendor/bar1.php', 'bar_vendor/bar2.php']
        );

        $disabler = $this->pluginDisabler();

        $disabler->disableAllForVendor('foo_vendor');
        $disabler->applyRules();

        $this->assertActivePluginsAre(['bar_vendor/bar1.php', 'bar_vendor/bar2.php']);

        $disabler = $this->pluginDisabler();

        $disabler->disableAllForVendor('bar_vendor');
        $disabler->applyRules();

        $this->assertActivePluginsAre(['foo_vendor/foo1.php', 'foo_vendor/foo2.php']);

        $disabler = $this->pluginDisabler();

        $disabler->disableAllForVendor(['bar_vendor', 'foo_vendor']);
        $disabler->applyRules();

        $this->assertActivePluginsAre([]);
    }

    /**
     * @test
     */
    public function that_vendor_plugins_can_be_removed_by_conditions(): void
    {
        $this->activePluginsAre(
            ['foo_vendor/foo1.php', 'foo_vendor/foo2.php', 'bar_vendor/bar1.php', 'bar_vendor/bar2.php']
        );

        $disabler = $this->pluginDisabler();

        $disabler->disableAllForVendor('foo_vendor', new AlwaysTrueCondition());
        $disabler->applyRules();
        $this->assertActivePluginsAre(['bar_vendor/bar1.php', 'bar_vendor/bar2.php']);

        $disabler = $this->pluginDisabler();

        $disabler->disableAllForVendor('foo_vendor', new AlwaysFalseCondition());
        $disabler->applyRules();

        $this->assertActivePluginsAre(
            ['foo_vendor/foo1.php', 'foo_vendor/foo2.php', 'bar_vendor/bar1.php', 'bar_vendor/bar2.php']
        );
    }

    /**
     * @test
     */
    public function that_an_event_is_dispatched_before_a_single_plugin_is_evaluated(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->disable('hello.php');

        $dispatcher->assertNotingDispatched();

        $disabler->applyRules();

        $this->assertActivePluginsAre(['akismet/akismet.php']);

        $dispatcher->assertDispatched(
            'snicco/performance:disabling_hello.php',
            fn (DisablingPlugin $event): bool => 'hello.php' === $event->pluginId()
        );
    }

    /**
     * @test
     */
    public function that_an_event_is_dispatched_after_a_plugin_was_disabled(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());
        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->disable('hello.php');

        $dispatcher->assertNotDispatched(PluginWasDisabled::class);

        $disabler->applyRules();

        $this->assertActivePluginsAre(['akismet/akismet.php']);

        $dispatcher->assertDispatched('snicco/performance:disabled_hello.php');
    }

    /**
     * @test
     */
    public function that_disabling_a_plugin_can_be_prevented_with_wp_filters(): void
    {
        $disabler = $this->pluginDisabler(
            $dispatcher = new TestableEventDispatcher(new WPEventDispatcher(new BaseEventDispatcher()))
        );

        add_filter('snicco/performance:disabling_hello.php', function (DisablingPlugin $event): void {
            $event->proceed = false;
        });

        $disabler->disable('hello.php');
        $disabler->applyRules();

        $this->assertActivePluginsAre(['hello.php', 'akismet/akismet.php']);

        $dispatcher->assertNotDispatched('snicco/performance:disabled_hello.php');
    }

    /**
     * @test
     */
    public function that_a_dependency_map_of_plugins_can_be_defined(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $disabler = $this->pluginDisabler();

        $disabler->addChainDisablingRules([
            'foo/foo.php' => ['bar/bar.php', 'baz/baz.php'],
        ]);

        $disabler->disable('foo/foo.php');

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);
    }

    /**
     * @test
     */
    public function that_disabling_a_dependent_plugin_triggers_the_disabled_event(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
        $disabler = $this->pluginDisabler($dispatcher = new TestableEventDispatcher(new BaseEventDispatcher()));

        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'bar/bar.php',
        ]);
        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'baz/baz.php',
        ]);

        $disabler->disable('foo/foo.php');

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);

        $dispatcher->assertDispatched('snicco/performance:disabled_foo/foo.php');
        $dispatcher->assertDispatched('snicco/performance:disabled_bar/bar.php');
        $dispatcher->assertDispatched('snicco/performance:disabled_baz/baz.php');
    }

    /**
     * @test
     */
    public function that_disabling_a_dependent_plugin_does_not_trigger_the_disabling_plugin_event(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
        $disabler = $this->pluginDisabler($dispatcher = new TestableEventDispatcher(new BaseEventDispatcher()));

        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'bar/bar.php',
        ]);
        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'baz/baz.php',
        ]);

        $disabler->disable('foo/foo.php');

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);

        $dispatcher->assertDispatched('snicco/performance:disabling_foo/foo.php');

        $dispatcher->assertNotDispatched('snicco/performance:disabling_bar/bar.php');
        $dispatcher->assertNotDispatched('snicco/performance:disabling_baz/baz.php');
    }

    /**
     * @test
     */
    public function that_disabled_plugin_interdependencies_only_fire_once_event_per_plugin(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
        $disabler = $this->pluginDisabler($dispatcher = new TestableEventDispatcher(new BaseEventDispatcher()));

        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'baz/baz.php',
        ]);
        $disabler->addChainDisablingRules([
            'bar/bar.php' => 'baz/baz.php',
        ]);

        $disabler->disable(['foo/foo.php', 'bar/bar.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);

        $dispatcher->assertDispatchedTimes('snicco/performance:disabled_foo/foo.php');
        $dispatcher->assertDispatchedTimes('snicco/performance:disabled_bar/bar.php');
        $dispatcher->assertDispatchedTimes('snicco/performance:disabled_baz/baz.php');
    }

    /**
     * @test
     */
    public function that_interdependencies_are_resolved_recursively(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
        $disabler = $this->pluginDisabler($dispatcher = new TestableEventDispatcher(new BaseEventDispatcher()));

        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'bar/bar.php',
        ]);
        $disabler->addChainDisablingRules([
            'bar/bar.php' => 'baz/baz.php',
        ]);

        $disabler->disable(['foo/foo.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);
    }

    /**
     * @test
     */
    public function that_a_circular_dependencies_does_not_result_in_a_segfault(): void
    {
        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
        $disabler = $this->pluginDisabler($dispatcher = new TestableEventDispatcher(new BaseEventDispatcher()));

        $disabler->addChainDisablingRules([
            'foo/foo.php' => 'bar/bar.php',
        ]);
        $disabler->addChainDisablingRules([
            'bar/bar.php' => 'baz/baz.php',
        ]);
        $disabler->addChainDisablingRules([
            'baz/baz.php' => 'foo/foo.php',
        ]);

        $disabler->disable(['foo/foo.php', 'baz/baz.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre([]);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_plugins_are_disabled_after_apply_rules_was_called(): void
    {
        $this->expectException(LogicException::class);
        $this->expectNoticeMessage(
            'Plugins can not be disabled anymore because PluginDisabler::applyRules() was already called.'
        );

        $disabler = $this->pluginDisabler();

        $disabler->applyRules();
        $disabler->disable('foo');
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_vendor_plugins_are_disabled_after_apply_rules_was_called(): void
    {
        $this->expectException(LogicException::class);
        $this->expectNoticeMessage(
            'Plugins can not be disabled anymore because PluginDisabler::applyRules() was already called.'
        );

        $disabler = $this->pluginDisabler();

        $disabler->applyRules();
        $disabler->disableAllForVendor('foo');
    }

    /**
     * @test
     */
    public function that_veto_rules_can_be_added_that_will_prevent_plugins_from_being_disabled_if_other_plugins_are_active(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());

        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->addFinalVetoRules([
            'bar/bar.php' => ['foo/foo.php'],
        ]);
        $disabler->disable('foo/foo.php');

        $disabler->applyRules();

        $this->assertActivePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $dispatcher->assertDispatched('snicco/performance:disabling_foo/foo.php');
        $dispatcher->assertNotDispatched('snicco/performance:disabled_foo/foo.php');
        $dispatcher->assertNotDispatched('snicco/performance:disabled_bar/bar.php');
        $dispatcher->assertNotDispatched('snicco/performance:disabled_baz/baz.php');
    }

    /**
     * @test
     */
    public function that_vetos_from_disabled_plugins_have_no_effect(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());

        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->addFinalVetoRules([
            'bar/bar.php' => ['foo/foo.php'],
        ]);
        $disabler->disable(['foo/foo.php', 'bar/bar.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre(['baz/baz.php']);
    }

    /**
     * @test
     */
    public function that_veto_rules_are_resolved_recursively(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());

        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->addFinalVetoRules([
            'foo/foo.php' => ['bar/bar.php'],
            'bar/bar.php' => 'baz/baz.php',
        ]);
        $disabler->disable(['bar/bar.php', 'baz/baz.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
    }

    /**
     * @test
     */
    public function that_circular_vetos_dont_cause_a_segfault(): void
    {
        $dispatcher = new TestableEventDispatcher(new BaseEventDispatcher());

        $this->activePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);

        $disabler = $this->pluginDisabler($dispatcher);

        $disabler->addFinalVetoRules([
            'foo/foo.php' => ['bar/bar.php'],
            'bar/bar.php' => 'baz/baz.php',
            'baz/baz.php' => ['bar/bar.php', 'foo/foo.php'],
        ]);
        $disabler->disable(['bar/bar.php', 'baz/baz.php']);

        $disabler->applyRules();

        $this->assertActivePluginsAre(['foo/foo.php', 'bar/bar.php', 'baz/baz.php']);
    }

    /**
     * @return string[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function fetchActivePlugins(): array
    {
        return (array) get_option('active_plugins');
    }

    /**
     * @param string[] $plugins
     */
    private function activePluginsAre(array $plugins): void
    {
        $res = update_option('active_plugins', $plugins);
        if (! $res) {
            throw new RuntimeException('Could not update option');
        }

        if (get_option('active_plugins') !== $plugins) {
            throw new RuntimeException('Could not update option correctly');
        }
    }

    /**
     * @param string[] $expected
     */
    private function assertActivePluginsAre(array $expected): void
    {
        $this->assertSame($expected, $this->fetchActivePlugins());
    }

    private function resetEverything(): void
    {
        remove_all_filters('option_active_plugins');
    }

    private function pluginDisabler(EventDispatcher $dispatcher = null): PluginDisabler
    {
        $dispatcher = $dispatcher ?: new BaseEventDispatcher();
        $this->resetEverything();

        return new PluginDisabler($dispatcher, new Context([], [], [], [],));
    }
}
