<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

/**
 * @return string[]
 */
function getWordPressStubs(string $name): array
{
    $file = \dirname(__DIR__) . '/vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $name;
    $contents = \file_get_contents($file);
    if (false === $contents) {
        throw new RuntimeException("Could not get contents of file {$file}");
    }

    return \json_decode($contents, true, \JSON_THROW_ON_ERROR);
}

/**
 * This can be configured to your likings.
 */
function generatePrefix(): string
{
    $hash = \md5((new DateTime('now'))->format('Ymd'));

    return 'VENDOR_NAMESPACE\\Scoped' . \substr($hash, 0, 8);
}

return [
    'prefix' => \generatePrefix(),
    'finders' => [
        Finder::create()->files()->in('src'),
        Finder::create()
            ->files()
            ->in('vendor')
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->notName('/README.*|CHANGELOG\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->notName('/phpunit\\.xml|psalm.*/')
            ->exclude(['sniccowp', 'bin', 'doc', 'test', 'test_old', 'tests', 'Tests', 'vendor-bin']),
        Finder::create()->files()
            ->in('boot')
            ->append(['composer.json', 'main.php', 'PLUGIN_BASENAME.php', 'LICENSE.md', 'uninstall.php']),
    ],

    'exclude-files' => [...require_once __DIR__ . '/symfony-polyfills.php'],

    'exclude-classes' => [...\getWordPressStubs('exclude-wordpress-classes.json'), 'WP_CLI'],

    'exclude-functions' => \array_merge(\getWordPressStubs('exclude-wordpress-functions.json'),),

    'exclude-constants' => \array_merge(
        ['VENDOR_CAPS_PLUGIN_ENV', 'VENDOR_CAPS_DELETE_DATA_ON_DELETION', 'VENDOR_CAPS_PLUGIN_DEBUG', 'WP_CLI'],
        ['/^SYMFONY\_[\p{L}_]+$/'],
        \getWordPressStubs('exclude-wordpress-constants.json')
    ),

    'exclude-namespaces' => [
        'VENDOR_NAMESPACE',
        'Symfony\Polyfill',
        'WP_CLI', // We don't have the WP_CLI package as a composer dependency, it is expected to be installed on the client site.
    ],
];
