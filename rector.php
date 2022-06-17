<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/rector.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/src/Monorepo',
        __DIR__ . '/src/Snicco/plugin',
        __DIR__ . '/src/Snicco/component',
        __DIR__ . '/src/Snicco/bundle',
        __DIR__ . '/bin/snicco.php',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/src/Snicco/bundle/fortress-bundle/tests/_support/_generated',
    ]);

    $rectorConfig->cacheDirectory('/tmp/snicco-qa/rector');
    $rectorConfig->parallel();
    $rectorConfig->importShortClasses();
    $rectorConfig->importNames();
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->sets([
        SetList::PHP_74,
    ]);
};
