<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use Webmozart\Glob\Glob;

return static fn (Configuration $config): Configuration => $config
    ->addNamedFilter(
        NamedFilter::fromString('nyholm/psr7')
    ) // This required for http-routing because it requires a psr-implementation.
    ->setAdditionalFilesFor('snicco/enterprise', [
        __DIR__ . '/src/Snicco/plugin/snicco-fortress/main.php',
        __DIR__ . '/src/Snicco/plugin/snicco-fortress/snicco-fortress.php',
        ...\array_merge(
            Glob::glob(__DIR__ . '/src/Snicco/plugin/snicco-fortress/config/*.php'),
            Glob::glob(__DIR__ . '/src/Snicco/plugin/snicco-fortress/boot/*.php'),
        ),
    ]);
