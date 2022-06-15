<?php

declare(strict_types=1);

/*
 * Plugin name: Fortress bundle Test MU plugin.
 * Description: Runs only during WP-CLI tests.
 */
if (! (\defined(WP_CLI::class))) {
    return;
}

if (! (\getenv('WPBROWSER_HOST_REQUEST'))) {
    return;
}
require_once __DIR__ . '/fortress-test-mu-plugin/index.php';
