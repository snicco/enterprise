<?php

declare(strict_types=1);

use Snicco\Enterprise\Monorepo\PHPScoper\StubList;

require_once __DIR__.'/vendor/autoload.php';

$stub_list = new StubList(__DIR__);

$wordpress_classes = $stub_list->wordpress('exclude-wordpress-classes.json');
$wordpress_functions = $stub_list->wordpress('exclude-wordpress-functions.json');
$wordpress_constants = $stub_list->wordpress('exclude-wordpress-constants.json');

unset($stub_list);

return [
    'exclude-classes' => \array_merge($wordpress_classes, ['WP_CLI']),

    'exclude-functions' => $wordpress_functions,

    'exclude-constants' => \array_merge(
        ['WP_CLI'],
        ['/^SYMFONY\_[\p{L}_]+$/'],
        $wordpress_constants
    ),

    'exclude-namespaces' => [
        'Snicco\Enterprise',
        'Symfony\Polyfill',
        'WP_CLI', // We don't have the WP_CLI package as a composer dependency, it is expected to be installed on the client site.
    ],
];
