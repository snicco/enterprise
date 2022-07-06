<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;
use Snicco\Enterprise\Monorepo\PHPScoper\ScoperPrefix;
use Snicco\Enterprise\Monorepo\PHPScoper\StubList;

$parent_config = require_once \dirname(__DIR__, 4) . '/php-scoper.php';

$stub_list = new StubList(\dirname(__DIR__, 4));

$plugin_config = [
    'prefix' => (string) new ScoperPrefix(),
    'finders' => [
        Finder::create()->files()->in(__DIR__ . '/src'),
        Finder::create()
            ->files()
            ->in(__DIR__ . '/vendor')
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->notName(
                '/README.*|CHANGELOG\\.md|CONTRIBUTING.*|.*\\.dist|Makefile|composer\\.json|composer\\.lock/'
            )
            ->notName('/codeception\\.dist\\.xml|phpunit\\.xml|psalm.*/')
            ->exclude(['sniccowp', 'bin', 'doc', 'test', 'test_old', 'tests', 'Tests', 'vendor-bin']),
        Finder::create()->files()
            ->in(__DIR__ . '/boot')
            ->append([__DIR__ . '/main.php', __DIR__ . '/snicco-fortress.php']),
        Finder::create()->files()->in(__DIR__ . '/config'),
    ],

    'exclude-files' => $stub_list->symfonyPolyFills(__DIR__),
];

return \array_merge($parent_config, $plugin_config);
