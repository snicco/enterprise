<?php

declare(strict_types=1);

// Include the WordPress stub files here. For some reason the performance is way better
// than including the stubs in the xml config.
// @see https://github.com/vimeo/psalm/issues/7570
require dirname(__DIR__).'/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';
require dirname(__DIR__).'/vendor/wp-cli/wp-cli/php/utils.php';

// Using global WordPress constants should be reduced to an absolute minimum.
// We need to tell psalm about them.
define('WP_PLUGIN_DIR', __DIR__);