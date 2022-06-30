<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

/**
 * @return string[]
 */
function getWordPressStubs(string $name): array
{
    $file = monorepoRoot().'/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $name;
    $contents = \file_get_contents($file);
    if (false === $contents) {
        throw new RuntimeException("Could not get contents of file {$file}");
    }

    $stubs = \json_decode($contents, true, \JSON_THROW_ON_ERROR);
    
    if(!is_array($stubs)){
        throw new RuntimeException("Stub file contents must be an array.");
    }
    /** @var string[] $stubs */
    return $stubs;
}

function getSymfonyPolyFills (string $plugin_dir) : array {
    $polyfills_bootstraps = \array_map(
        static fn (SplFileInfo $fileInfo) => $fileInfo->getPathname(),
        \iterator_to_array(
            Finder::create()
                  ->in($plugin_dir . '/vendor/symfony')
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
                  ->in($plugin_dir . '/vendor/symfony')
                  ->files()
                  ->path('/^polyfill-[\w\-_]+\/Resources/')
                  ->name('*.php'),
        ),
    );
    
    return \array_values(\array_merge($polyfills_bootstraps, $polyfills_stubs));
}

function generatePrefix(): string
{
    return 'Snicco\\Scoped';
}

function getStringFromServer(string $key): string {
    if(!isset($_SERVER[$key])) {
        throw new RuntimeException("Key $key is not set in \$_SERVER.");
    }
    $value = $_SERVER[$key];
    
    if(is_string($value)) {
        throw new RuntimeException("Key $key is not a string in \$_SERVER.");
    }
    return $value;
}

function monorepoRoot() :string {
    $monorepo_root = (string) getcwd();
    
    if(!is_dir($monorepo_root) || ! is_dir($monorepo_root.'/src/Snicco')){
        throw new RuntimeException("$monorepo_root is not the root of the monorepo.");
    }
    return $monorepo_root;
}

return [
    //'prefix' => \generatePrefix(),
    //'finders' => [
    //    Finder::create()->files()->in($plugin_dir.'src'),
    //    Finder::create()
    //        ->files()
    //        ->in($plugin_dir.'vendor')
    //        ->ignoreVCS(true)
    //        ->ignoreDotFiles(true)
    //        ->notName('/README.*|CHANGELOG\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
    //        ->notName('/phpunit\\.xml|psalm.*/')
    //        ->exclude(['sniccowp', 'bin', 'doc', 'test', 'test_old', 'tests', 'Tests', 'vendor-bin']),
    //    Finder::create()->files()
    //        ->in($plugin_dir.'boot')
    //        ->append(['composer.json', 'main.php', 'PLUGIN_BASENAME.php', 'LICENSE.md', 'uninstall.php']),
    //],

    //'exclude-files' => [...getSymfonyPolyFills()],

    'exclude-classes' => [...\getWordPressStubs('exclude-wordpress-classes.json'), 'WP_CLI'],

    'exclude-functions' => \array_merge(\getWordPressStubs('exclude-wordpress-functions.json'),),

    'exclude-constants' => \array_merge(
        ['WP_CLI'],
        ['/^SYMFONY\_[\p{L}_]+$/'],
        \getWordPressStubs('exclude-wordpress-constants.json')
    ),

    'exclude-namespaces' => [
        'Snicco\Enterprise',
        'Symfony\Polyfill',
        'WP_CLI', // We don't have the WP_CLI package as a composer dependency, it is expected to be installed on the client site.
    ],
];
