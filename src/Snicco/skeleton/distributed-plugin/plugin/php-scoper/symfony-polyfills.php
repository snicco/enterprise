<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$polyfills_bootstraps = \array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    \iterator_to_array(
        Finder::create()
            ->in(\dirname(__DIR__) . '/vendor/symfony')
            ->files()
            ->path('/^polyfill-[\w\-_]+/')
            ->name('bootstrap*.php'),
        false,
    ),
);

$polyfills_stubs = \array_map(
    static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
    \iterator_to_array(
        Finder::create()
            ->in(\dirname(__DIR__) . '/vendor/symfony')
            ->files()
            ->path('/^polyfill-[\w\-_]+\/Resources/')
            ->name('*.php'),
    ),
);

return \array_values(\array_merge($polyfills_bootstraps, $polyfills_stubs));
