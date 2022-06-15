<?php

declare(strict_types=1);

/*
 * Plugin name: Fortress bundle Test MU plugin.
 * Plugin description: Runs only during WP-CLI tests.
 */
$wp_browser = \getenv('WPBROWSER_HOST_REQUEST');
$cli = \defined(WP_CLI::class);
if (! $cli) {
    return;
}

if (! $wp_browser) {
    return;
}

require_once __DIR__ . '/fortress-test-mu-plugin/index.php';
