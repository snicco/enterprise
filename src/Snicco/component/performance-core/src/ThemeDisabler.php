<?php

declare(strict_types=1);

namespace Snicco\Enterprise\Component\Performance\Core;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Enterprise\Component\Condition\Condition;
use Snicco\Enterprise\Component\Condition\Context;

use Snicco\Enterprise\Component\Performance\Core\Event\DisablingTheme;
use Snicco\Enterprise\Component\Performance\Core\Event\ThemeWasDisabled;

use function add_filter;
use function dirname;

use const PHP_INT_MAX;

final class ThemeDisabler
{
    private Context         $context;

    private EventDispatcher $event_dispatcher;

    public function __construct(EventDispatcher $event_dispatcher, Context $context)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->context = $context;
    }

    public function applyRules(?Condition $condition = null): void
    {
        if ($condition && ! $condition->isTruthy($this->context)) {
            return;
        }

        if (! $this->event_dispatcher->dispatch(new DisablingTheme($this->context))->proceed) {
            return;
        }

        $this->event_dispatcher->dispatch(new ThemeWasDisabled($this->context));

        $theme_dir = dirname(__DIR__) . '/resources/themes';

        add_filter('template_directory', fn (): string => $theme_dir . '/snicco-performance-theme', PHP_INT_MAX);
        add_filter('stylesheet_directory', fn (): string => $theme_dir . '/snicco-performance-theme', PHP_INT_MAX);
        add_filter('pre_option_stylesheet', fn (): string => 'snicco-performance-theme');
        add_filter('pre_option_template', fn (): string => 'snicco-performance-theme');
        add_filter('theme_root', fn (): string => $theme_dir);
    }
}
