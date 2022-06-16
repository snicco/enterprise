<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/rector.php',
    ]);

    $rectorConfig->cacheDirectory('/tmp/snicco-qa/rector');
    $rectorConfig->parallel();

    $tokens = [];
    $token = 4;
    $tokens[] = $token;
    $token = 4;
    $tokens[] = $token;

    // register a single rule
    $rectorConfig->rule(AndAssignsToSeparateLinesRector::class);

    // define sets of rules
    //    $rectorConfig->sets([
    //        LevelSetList::UP_TO_PHP_74
    //    ]);
};
