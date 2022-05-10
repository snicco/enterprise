<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core;

use LogicException;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;
use Snicco\Enterprise\Component\Performance\Core\Event\DisablingPlugin;
use Snicco\Enterprise\Component\Performance\Core\Event\PluginWasDisabled;

use function add_action;
use function add_filter;
use function array_diff;
use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function remove_filter;

use const PHP_INT_MIN;

final class PluginDisabler
{
    /**
     * A list of plugin identifiers in the formant <plugin>/<plugin.php>.
     *
     * @var array<string,string>
     */
    private array $plugins_to_disable = [];

    /**
     * A list of plugin vendors that should be disabled entirely.
     *
     * @var array<string,string>
     */
    private array $vendors_to_disable = [];

    /**
     * An array where each key is a plugin id {@see self::plugins_to_disable}
     * and each value is a list of plugin identifiers that should always be
     * disabled if the "key-plugin" is disabled. The rules are resolved
     * recursively.
     *
     * @var array<string,string[]>
     */
    private array $plugin_chain_disabling_rules = [];

    /**
     * An array where each key is a plugin id {@see self::plugins_to_disable}
     * and each value is a list of plugin identifiers that should NOT be
     * disabled if the "key-plugin" stays active. The rules are resolved
     * recursively and have a higher priority as chain disabled plugins.
     *
     * @var array<string,string[]>
     */
    private array $final_veto_rules = [];

    /**
     * @var array<string,string[]>
     */
    private array $plugin_interdependencies_compiled = [];

    /**
     * @var array<string,string[]>
     */
    private array $plugin_vetos_compiled = [];

    private bool $rules_were_applied = false;

    private EventDispatcher $event_dispatcher;

    private Context $context;

    public function __construct(EventDispatcher $event_dispatcher, Context $context)
    {
        $this->context = $context;
        $this->event_dispatcher = $event_dispatcher;
    }

    /**
     * @param string|string[] $plugin_ids
     */
    public function disable($plugin_ids, Condition $condition = null): void
    {
        if ($this->rules_were_applied) {
            throw new LogicException(
                'Plugins can not be disabled anymore because PluginDisabler::applyRules() was already called.'
            );
        }

        if (null !== $condition && ! $condition->isTruthy($this->context)) {
            return;
        }

        $ids = Arr::toArray($plugin_ids);

        foreach ($ids as $id) {
            $this->plugins_to_disable[$id] = $id;
        }
    }

    /**
     * @param string|string[] $vendor_prefix
     */
    public function disableAllForVendor($vendor_prefix, Condition $condition = null): void
    {
        if ($this->rules_were_applied) {
            throw new LogicException(
                'Plugins can not be disabled anymore because PluginDisabler::applyRules() was already called.'
            );
        }

        if (null !== $condition && ! $condition->isTruthy($this->context)) {
            return;
        }

        $prefixes = Arr::toArray($vendor_prefix);

        foreach ($prefixes as $id) {
            $this->vendors_to_disable[$id] = $id;
        }
    }

    /**
     * @param array<string,string|string[]> $veto_rules
     *
     * @see self::final_veto_rules
     */
    public function addFinalVetoRules(array $veto_rules): void
    {
        foreach ($veto_rules as $plugin_id => $vetos) {
            $current = $this->final_veto_rules[$plugin_id] ?? [];

            $this->final_veto_rules[$plugin_id] = array_merge($current, Arr::toArray($vetos));
        }
    }

    /**
     * @param array<string,string|string[]> $interdependencies
     *
     * @see self::plugin_chain_disabling_rules
     */
    public function addChainDisablingRules(array $interdependencies): void
    {
        foreach ($interdependencies as $plugin_id => $dependents) {
            $current = $this->plugin_chain_disabling_rules[$plugin_id] ?? [];

            $this->plugin_chain_disabling_rules[$plugin_id] = array_merge($current, Arr::toArray($dependents));
        }
    }

    public function applyRules(): void
    {
        if ($this->rules_were_applied) {
            return;
        }

        $callback = function (array $active_plugins): array {
            $active_plugins = array_filter($active_plugins, 'is_string');

            return $this->filterPlugins($active_plugins);
        };

        add_filter('option_active_plugins', $callback, PHP_INT_MIN);

        add_action('plugin_loaded', function () use ($callback): void {
            remove_filter('option_active_plugins', $callback, PHP_INT_MIN);
        });

        $this->rules_were_applied = true;
    }

    /**
     * @param string[] $wordpress_active_plugins
     *
     * @return string[]
     */
    private function filterPlugins(array $wordpress_active_plugins): array
    {
        $disabled_plugins = [];

        foreach ($wordpress_active_plugins as $plugin_id) {
            $should_be_disabled = isset($this->plugins_to_disable[$plugin_id])
                                  || $this->isDisabledVendorPlugin($plugin_id);

            if (! $should_be_disabled) {
                continue;
            }

            $event = $this->event_dispatcher->dispatch(new DisablingPlugin($plugin_id));

            if (! $event->proceed) {
                continue;
            }

            $disabled_plugins[] = $plugin_id;
            $disabled_plugins = array_unique(
                array_merge($disabled_plugins, $this->resolveDependentsRecursive($plugin_id))
            );
        }

        [$active_plugins, $disabled_plugins] = $this->applyVetos($wordpress_active_plugins, $disabled_plugins);

        foreach ($disabled_plugins as $disabled_plugin) {
            $this->event_dispatcher->dispatch(new PluginWasDisabled($disabled_plugin));
        }

        return $active_plugins;
    }

    private function isDisabledVendorPlugin(string $plugin_id): bool
    {
        foreach ($this->vendors_to_disable as $disabled_vendor) {
            if (Str::startsWith($plugin_id, $disabled_vendor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $wordpress_active_plugins
     * @param string[] $disabled_plugins
     *
     * @return array{0:list<string>, 1: string[]}
     */
    private function applyVetos(array $wordpress_active_plugins, array $disabled_plugins): array
    {
        $runtime_active_plugins = array_diff($wordpress_active_plugins, $disabled_plugins);

        foreach ($runtime_active_plugins as $runtime_active_plugin) {
            if (! isset($this->final_veto_rules[$runtime_active_plugin])) {
                continue;
            }

            $vetos = $this->resolveVetosRecursive($runtime_active_plugin);
            $disabled_plugins = array_diff($disabled_plugins, $vetos);
        }

        return [array_values(array_diff($wordpress_active_plugins, $disabled_plugins)), $disabled_plugins];
    }

    /**
     * @return string[]
     */
    private function resolveVetosRecursive(string $active_plugin_id): array
    {
        if (isset($this->plugin_vetos_compiled[$active_plugin_id])) {
            return $this->plugin_vetos_compiled[$active_plugin_id];
        }

        /** @var array<string,true> $currently_building */
        static $currently_building = [];

        $currently_building[$active_plugin_id] = true;

        $initial = $this->final_veto_rules[$active_plugin_id] ?? [];

        foreach ($initial as $id) {
            if (! isset($this->final_veto_rules[$id])) {
                continue;
            }

            if (isset($currently_building[$id])) {
                continue;
            }

            $initial = array_merge($initial, $this->resolveVetosRecursive($id));
        }

        unset($currently_building[$active_plugin_id]);
        if ([] === $currently_building) {
            unset($currently_building);
        }

        $initial = array_unique($initial);

        $this->plugin_vetos_compiled[$active_plugin_id] = $initial;

        return $initial;
    }

    /**
     * @return string[]
     */
    private function resolveDependentsRecursive(string $plugin_id): array
    {
        if (isset($this->plugin_interdependencies_compiled[$plugin_id])) {
            return $this->plugin_interdependencies_compiled[$plugin_id];
        }

        /** @var array<string,true> $currently_building */
        static $currently_building = [];

        $currently_building[$plugin_id] = true;

        $initial = $this->plugin_chain_disabling_rules[$plugin_id] ?? [];

        foreach ($initial as $id) {
            if (! isset($this->plugin_chain_disabling_rules[$id])) {
                continue;
            }

            if (isset($currently_building[$id])) {
                continue;
            }

            $initial = array_merge($initial, $this->resolveDependentsRecursive($id));
        }

        unset($currently_building[$plugin_id]);
        if ([] === $currently_building) {
            unset($currently_building);
        }

        $initial = array_unique($initial);

        $this->plugin_interdependencies_compiled[$plugin_id] = $initial;

        return $initial;
    }
}
